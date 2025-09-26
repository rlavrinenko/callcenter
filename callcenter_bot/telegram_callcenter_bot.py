"""
Телеграм-бот для бази callcenter (спрощений).

Функції:
  - /add_person — послідовно запитує у адміністратора ПІБ, дату народження та номер телефону, після чого додає до бази.
  - /find_person — запитує прізвище (мін. 3 літери), знаходить збіги і показує з інлайн кнопками «Додати до черги».
  - /queue — запитує прізвище (мін. 3 літери), показує збіги з інлайн кнопками «Прибув» (оновлює статус=removed) та «Видалити» (видаляє).

Вимоги:
  pip install pyTelegramBotAPI mysql-connector-python python-dotenv

.env:
  TELEGRAM_TOKEN=...

config.py:
  DB_HOST = 'localhost'
  DB_PORT = 3306
  DB_USER = 'root'
  DB_PASS = '...'
  DB_NAME = 'callcenter'
  ADMIN_IDS = [123456789, 987654321]  # список ID адміністраторів
"""

import os
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv
import telebot
from telebot import types
import config

load_dotenv()
TELEGRAM_TOKEN = os.getenv('TELEGRAM_TOKEN')
if not TELEGRAM_TOKEN:
    raise SystemExit('Set TELEGRAM_TOKEN in .env')

bot = telebot.TeleBot(TELEGRAM_TOKEN, parse_mode=None)

# ========== DB ==========
def get_db_connection():
    return mysql.connector.connect(
        host=config.DB_HOST,
        port=config.DB_PORT,
        user=config.DB_USER,
        password=config.DB_PASS,
        database=config.DB_NAME,
        autocommit=True,
    )


def fetchall(query, params=None):
    conn = get_db_connection()
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute(query, params or ())
        rows = cur.fetchall()
        cur.close()
        return rows
    finally:
        conn.close()


def execute(query, params=None):
    conn = get_db_connection()
    try:
        cur = conn.cursor()
        cur.execute(query, params or ())
        last = cur.lastrowid
        cur.close()
        return last
    finally:
        conn.close()

# ========== ACCESS CONTROL ==========
def is_admin(user_id):
    return user_id in config.ADMIN_IDS


# ========== COMMANDS ==========
@bot.message_handler(commands=['start', 'help'])
def cmd_help(message):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    bot.send_message(message.chat.id,
                     "Команди:\n"
                     "/add_person — додати особу (запитує дані).\n"
                     "/find_person — знайти особу (мін. 3 літери).\n"
                     "/queue — пошук у черзі (мін. 3 літери).")

# ====== ADD PERSON ======
@bot.message_handler(commands=['add_person'])
def cmd_add_person(message):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    msg = bot.send_message(message.chat.id, "Введіть ПІБ:")
    bot.register_next_step_handler(msg, add_person_step_name)

def add_person_step_name(message):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    full_name = message.text.strip()
    msg = bot.send_message(message.chat.id, "Введіть дату народження (YYYY-MM-DD):")
    bot.register_next_step_handler(msg, add_person_step_dob, full_name)

def add_person_step_dob(message, full_name):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    dob = message.text.strip()
    msg = bot.send_message(message.chat.id, "Введіть номер телефону:")
    bot.register_next_step_handler(msg, add_person_step_phone, full_name, dob)

def add_person_step_phone(message, full_name, dob):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    phone = message.text.strip()
    try:
        person_id = execute('INSERT INTO persons (full_name, birth_date) VALUES (%s,%s)', (full_name, dob))
        execute('INSERT INTO numbers (phone, owner_id) VALUES (%s,%s)', (phone, person_id))
        bot.send_message(message.chat.id, f'Особа {full_name}, {dob}, номер {phone} додані.')
    except Error as e:
        bot.send_message(message.chat.id, f'Помилка: {e}')

# ====== FIND PERSON ======
@bot.message_handler(commands=['find_person'])
def cmd_find_person(message):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    msg = bot.send_message(message.chat.id, "Введіть початок прізвища (мін. 3 літери):")
    bot.register_next_step_handler(msg, do_find_person)

def do_find_person(message):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    prefix = message.text.strip()
    if len(prefix) < 3:
        bot.send_message(message.chat.id, "Мінімум 3 літери")
        return
    rows = fetchall("SELECT id, full_name FROM persons WHERE full_name LIKE %s LIMIT 20", (prefix+"%",))
    if not rows:
        bot.send_message(message.chat.id, "Не знайдено")
        return
    kb = types.InlineKeyboardMarkup()
    for r in rows:
        kb.add(types.InlineKeyboardButton(text=r['full_name'], callback_data=f"toqueue:{r['id']}"))
    bot.send_message(message.chat.id, "Знайдені особи:", reply_markup=kb)

@bot.callback_query_handler(func=lambda call: call.data.startswith('toqueue:'))
def cb_toqueue(call):
    if not is_admin(call.from_user.id):
        bot.send_message(call.message.chat.id, "В доступі відмовлено")
        return
    person_id = call.data.split(':')[1]
    rows = fetchall("SELECT phone FROM numbers WHERE owner_id=%s LIMIT 1", (person_id,))
    if not rows:
        bot.send_message(call.message.chat.id, "У особи немає номера")
        return
    phone = rows[0]['phone']
    execute('INSERT INTO numbers (phone, owner_id, status) VALUES (%s,%s,%s)', (phone, person_id, 'queue'))
    bot.send_message(call.message.chat.id, f'Номер {phone} додано до черги.')

# ====== QUEUE ======
@bot.message_handler(commands=['queue'])
def cmd_queue(message):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    msg = bot.send_message(message.chat.id, "Введіть початок прізвища (мін. 3 літери):")
    bot.register_next_step_handler(msg, do_queue)

def do_queue(message):
    if not is_admin(message.from_user.id):
        bot.send_message(message.chat.id, "В доступі відмовлено")
        return
    prefix = message.text.strip()
    if len(prefix) < 3:
        bot.send_message(message.chat.id, "Мінімум 3 літери")
        return
    rows = fetchall("SELECT n.id, n.phone, p.full_name, n.status FROM numbers n LEFT JOIN persons p ON n.owner_id=p.id WHERE p.full_name LIKE %s ORDER BY n.id ASC", (prefix+"%",))
    if not rows:
        bot.send_message(message.chat.id, "Не знайдено у черзі")
        return
    for r in rows:
        kb = types.InlineKeyboardMarkup()
        kb.add(
            types.InlineKeyboardButton("Прибув", callback_data=f"arrived:{r['id']}"),
            types.InlineKeyboardButton("Видалити", callback_data=f"delnum:{r['id']}")
        )
        bot.send_message(message.chat.id, f"{r['full_name']} — {r['phone']} [{r['status']}]", reply_markup=kb)

@bot.callback_query_handler(func=lambda call: call.data.startswith('arrived:'))
def cb_arrived(call):
    if not is_admin(call.from_user.id):
        bot.send_message(call.message.chat.id, "В доступі відмовлено")
        return
    num_id = call.data.split(':')[1]
    execute("UPDATE numbers SET status='removed' WHERE id=%s", (num_id,))
    bot.edit_message_text(chat_id=call.message.chat.id,
                          message_id=call.message.message_id,
                          text=f"ID {num_id}: статус змінено на removed")

@bot.callback_query_handler(func=lambda call: call.data.startswith('delnum:'))
def cb_delnum(call):
    if not is_admin(call.from_user.id):
        bot.send_message(call.message.chat.id, "В доступі відмовлено")
        return
    num_id = call.data.split(':')[1]
    execute("DELETE FROM numbers WHERE id=%s", (num_id,))
    bot.edit_message_text(chat_id=call.message.chat.id,
                          message_id=call.message.message_id,
                          text=f"ID {num_id}: видалено")

if __name__ == '__main__':
    print('Bot started...')
    bot.infinity_polling()
