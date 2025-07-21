# app.py
from flask import Flask, render_template, request, redirect, url_for, session, flash, send_from_directory
from flask_mysqldb import MySQL
from werkzeug.security import generate_password_hash, check_password_hash
import matplotlib.pyplot as plt
import io
import base64
import pandas as pd
from datetime import datetime, timedelta
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from config import Config # Importar la configuración

app = Flask(__name__)
app.config.from_object(Config) # Cargar la configuración

mysql = MySQL(app)

# --- Funciones de Ayuda ---
def is_logged_in(f):
    def wrap(*args, **kwargs):
        if 'logged_in' in session:
            return f(*args, **kwargs)
        else:
            flash('Por favor, inicia sesión para acceder a esta página.', 'danger')
            return redirect(url_for('login'))
    wrap.__name__ = f.__name__ # Importante para Flask
    return wrap

def send_email_notification(to_email, subject, body):
    msg = MIMEMultipart()
    msg['From'] = app.config['MAIL_USERNAME']
    msg['To'] = to_email
    msg['Subject'] = subject

    msg.attach(MIMEText(body, 'plain'))

    try:
        server = smtplib.SMTP(app.config['MAIL_SERVER'], app.config['MAIL_PORT'])
        server.starttls()
        server.login(app.config['MAIL_USERNAME'], app.config['MAIL_PASSWORD'])
        text = msg.as_string()
        server.sendmail(app.config['MAIL_USERNAME'], to_email, text)
        server.quit()
        print(f"Correo enviado a {to_email}")
    except Exception as e:
        print(f"Error al enviar correo a {to_email}: {e}")

# --- Rutas ---

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        nombre_usuario = request.form['username']
        correo_electronico = request.form['email']
        contrasena = request.form['password']
        confirm_contrasena = request.form['confirm_password']

        if contrasena != confirm_contrasena:
            flash('Las contraseñas no coinciden.', 'danger')
            return redirect(url_for('register'))

        hashed_password = generate_password_hash(contrasena)

        cur = mysql.connection.cursor()
        try:
            cur.execute("INSERT INTO usuarios (nombre_usuario, correo_electronico, contrasena) VALUES (%s, %s, %s)",
                        (nombre_usuario, correo_electronico, hashed_password))
            mysql.connection.commit()
            flash('Te has registrado exitosamente. Por favor, inicia sesión.', 'success')
            return redirect(url_for('login'))
        except Exception as e:
            flash(f'Error al registrar usuario: {e}', 'danger')
        finally:
            cur.close()
    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        correo_electronico = request.form['email']
        contrasena_candidata = request.form['password']

        cur = mysql.connection.cursor()
        result = cur.execute("SELECT * FROM usuarios WHERE correo_electronico = %s", [correo_electronico])

        if result > 0:
            data = cur.fetchone()
            user_id = data[0]
            nombre_usuario = data[1]
            contrasena = data[3]

            if check_password_hash(contrasena, contrasena_candidata):
                session['logged_in'] = True
                session['user_id'] = user_id
                session['username'] = nombre_usuario
                flash('Has iniciado sesión exitosamente.', 'success')
                return redirect(url_for('dashboard'))
            else:
                flash('Contraseña incorrecta.', 'danger')
                return redirect(url_for('login'))
        else:
            flash('Correo electrónico no encontrado.', 'danger')
            return redirect(url_for('login'))
        cur.close()
    return render_template('login.html')

@app.route('/logout')
@is_logged_in
def logout():
    session.clear()
    flash('Has cerrado sesión.', 'success')
    return redirect(url_for('login'))

@app.route('/dashboard')
@is_logged_in
def dashboard():
    user_id = session['user_id']
    cur = mysql.connection.cursor()

    # Gastos del mes actual
    current_month_start = datetime.now().replace(day=1).strftime('%Y-%m-%d')
    current_month_end = (datetime.now().replace(day=28) + timedelta(days=4)).replace(day=1) - timedelta(days=1)
    current_month_end = current_month_end.strftime('%Y-%m-%d')

    cur.execute("SELECT descripcion, monto, tipo, fecha FROM gastos WHERE usuario_id = %s AND fecha BETWEEN %s AND %s ORDER BY fecha DESC",
                (user_id, current_month_start, current_month_end))
    gastos_mes = cur.fetchall()

    # Próximos pagos (próximos 7 días)
    today = datetime.now().strftime('%Y-%m-%d')
    seven_days_later = (datetime.now() + timedelta(days=7)).strftime('%Y-%m-%d')

    cur.execute("SELECT descripcion, monto, fecha_pago FROM gastos WHERE usuario_id = %s AND fecha_pago BETWEEN %s AND %s ORDER BY fecha_pago ASC",
                (user_id, today, seven_days_later))
    proximos_pagos = cur.fetchall()

    # Generar gráfico de gastos (ejemplo simple, puedes expandirlo)
    gastos_data = []
    cur.execute("SELECT tipo, SUM(monto) FROM gastos WHERE usuario_id = %s GROUP BY tipo", [user_id])
    for row in cur.fetchall():
        gastos_data.append(row)

    if gastos_data:
        df = pd.DataFrame(gastos_data, columns=['Tipo', 'Monto'])
        plt.figure(figsize=(8, 6))
        plt.bar(df['Tipo'], df['Monto'], color=['skyblue', 'lightcoral'])
        plt.title('Gastos por Tipo (Personal vs. Empresarial)')
        plt.xlabel('Tipo de Gasto')
        plt.ylabel('Monto Total')
        plt.tight_layout()

        # Guardar gráfico en memoria y codificar a base64
        buf = io.BytesIO()
        plt.savefig(buf, format='png')
        buf.seek(0)
        graph_base64 = base64.b64encode(buf.getvalue()).decode('utf-8')
        plt.close()
    else:
        graph_base64 = None
    
    cur.close()
    return render_template('dashboard.html', gastos_mes=gastos_mes, proximos_pagos=proximos_pagos, graph_base64=graph_base64)


@app.route('/add_expense', methods=['GET', 'POST'])
@is_logged_in
def add_expense():
    if request.method == 'POST':
        descripcion = request.form['description']
        monto = request.form['amount']
        tipo = request.form['type']
        fecha = request.form['date']
        fecha_pago = request.form.get('payment_date') # Puede ser None

        user_id = session['user_id']

        cur = mysql.connection.cursor()
        try:
            if fecha_pago:
                cur.execute("INSERT INTO gastos (usuario_id, descripcion, monto, tipo, fecha, fecha_pago) VALUES (%s, %s, %s, %s, %s, %s)",
                            (user_id, descripcion, monto, tipo, fecha, fecha_pago))
            else:
                cur.execute("INSERT INTO gastos (usuario_id, descripcion, monto, tipo, fecha) VALUES (%s, %s, %s, %s, %s)",
                            (user_id, descripcion, monto, tipo, fecha))
            mysql.connection.commit()
            flash('Gasto agregado exitosamente.', 'success')
            return redirect(url_for('dashboard'))
        except Exception as e:
            flash(f'Error al agregar gasto: {e}', 'danger')
        finally:
            cur.close()
    return render_template('add_expense.html')

@app.route('/expense_history', methods=['GET', 'POST'])
@is_logged_in
def expense_history():
    user_id = session['user_id']
    gastos = []
    start_date = None
    end_date = None

    if request.method == 'POST':
        start_date = request.form.get('start_date')
        end_date = request.form.get('end_date')

    cur = mysql.connection.cursor()
    try:
        if start_date and end_date:
            cur.execute("SELECT descripcion, monto, tipo, fecha, fecha_pago FROM gastos WHERE usuario_id = %s AND fecha BETWEEN %s AND %s ORDER BY fecha DESC",
                        (user_id, start_date, end_date))
        else:
            cur.execute("SELECT descripcion, monto, tipo, fecha, fecha_pago FROM gastos WHERE usuario_id = %s ORDER BY fecha DESC",
                        [user_id])
        gastos = cur.fetchall()
    except Exception as e:
        flash(f'Error al cargar historial de gastos: {e}', 'danger')
    finally:
        cur.close()
    return render_template('expense_history.html', gastos=gastos, start_date=start_date, end_date=end_date)


@app.route('/check_payments')
def check_payments():
    cur = mysql.connection.cursor()
    today = datetime.now().date()
    # Busca pagos para mañana
    tomorrow = today + timedelta(days=1)

    cur.execute("""
        SELECT u.correo_electronico, g.descripcion, g.monto, g.fecha_pago
        FROM gastos g
        JOIN usuarios u ON g.usuario_id = u.id
        WHERE g.fecha_pago = %s
    """, [tomorrow])
    
    payments_due_tomorrow = cur.fetchall()

    for payment in payments_due_tomorrow:
        email, description, amount, payment_date = payment
        subject = f"Recordatorio de pago: {description} para mañana"
        body = f"Hola,\n\nEste es un recordatorio de que tienes un pago pendiente de '{description}' por un monto de ${amount} con fecha de pago el {payment_date}.\n\n¡Gracias!"
        send_email_notification(email, subject, body)
    
    cur.close()
    return "Verificación de pagos ejecutada." # Esto se ejecutaría con un cron job o similar

if __name__ == '__main__':
    app.run(debug=True)