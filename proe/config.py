# config.py

class Config:
    MYSQL_HOST = 'localhost'
    MYSQL_USER = 'root'  # Usuario por defecto de XAMPP
    MYSQL_PASSWORD = ''  # Contraseña por defecto de XAMPP (vacía)
    MYSQL_DB = 'gestor_gastos'
    SECRET_KEY = 'tu_clave_secreta_aqui' # Cambia esto por una clave segura
    MAIL_SERVER = 'smtp.gmail.com' # Ejemplo para Gmail
    MAIL_PORT = 587
    MAIL_USE_TLS = True
    MAIL_USERNAME = 'jpmfsalazar@gmail.com' # Tu correo
    MAIL_PASSWORD = 'xDDDDD_123' # Contraseña de aplicación