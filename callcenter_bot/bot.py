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
bot.infinity_polling()