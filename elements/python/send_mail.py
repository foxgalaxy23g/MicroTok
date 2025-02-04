import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import sys

def send_verification_email(to_email, verification_code, ip_address, user_agent, project_name):
    from_email = ""  # Your email address
    from_password = ""  # Your email password
    subject = f"Login Attempt on {project_name} Account"

    # HTML Body with styles
    body = f"""
    <html>
    <head>
        <style>
            body {{
                font-family: Arial, sans-serif;
                color: #333;
            }}
            .container {{
                width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 10px;
                background-color: #f9f9f9;
            }}
            .header {{
                text-align: center;
                margin-bottom: 20px;
            }}
            .logo {{
                width: 150px;
            }}
            .content {{
                font-size: 16px;
                line-height: 1.6;
            }}
            .verification-code {{
                font-size: 18px;
                font-weight: bold;
                color: #4CAF50;
            }}
            .footer {{
                font-size: 14px;
                color: #888;
                text-align: center;
                margin-top: 20px;
            }}
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="cid:logo" class="logo" alt="Logo">
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>We noticed a login attempt to your {project_name} account from an unrecognized device.</p>
                <p><strong>Verification Code:</strong> <span class="verification-code">{verification_code}</span></p>
                <p>If you did not initiate this login attempt, please disregard this email.</p>
                <p><strong>Login Details:</strong></p>
                <ul>
                    <li><strong>IP Address:</strong> {ip_address}</li>
                    <li><strong>User Agent:</strong> {user_agent}</li>
                </ul>
                <p>For security purposes, if this wasn't you, please change your password immediately.</p>
            </div>
            <div class="footer">
                <p>Best regards, <br>{project_name} Team</p>
            </div>
        </div>
    </body>
    </html>
    """

    msg = MIMEMultipart()
    msg['From'] = from_email
    msg['To'] = to_email
    msg['Subject'] = subject

    # Attach logo image
    with open("elements/embeded/me/logo-header.png", "rb") as logo_file:
        logo_data = logo_file.read()
        logo_attachment = MIMEText(logo_data, 'base64', 'utf-8')
        logo_attachment.add_header('Content-ID', '<logo>')
        msg.attach(logo_attachment)

    # Attach HTML body
    msg.attach(MIMEText(body, 'html'))

    try:
        # Set up SMTP server for Gmail
        server = smtplib.SMTP('smtp.gmail.com', 587)
        server.starttls()
        server.login(from_email, from_password)
        text = msg.as_string()
        server.sendmail(from_email, to_email, text)
        server.quit()
        print("Email sent successfully")
    except Exception as e:
        print(f"Failed to send email: {e}")

if __name__ == "__main__":
    to_email = sys.argv[1]
    verification_code = sys.argv[2]
    ip_address = sys.argv[3]  # Pass IP address as an argument
    user_agent = sys.argv[4]  # Pass User-Agent as an argument
    project_name = sys.argv[5]  # Pass project name as an argument
    send_verification_email(to_email, verification_code, ip_address, user_agent, project_name)
