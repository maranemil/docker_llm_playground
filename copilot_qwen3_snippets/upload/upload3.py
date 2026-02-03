"""
You can create a simple file upload form in Python in multiple ways — the easiest is to use a lightweight web framework like Flask.
Below is a complete, runnable example that lets users upload a file via a browser and saves it to a folder.

Python Flask File Upload Example
Python
"""


from flask import Flask, request, render_template_string
import os
from werkzeug.utils import secure_filename

app = Flask(__name__)

# Folder to store uploaded files
UPLOAD_FOLDER = 'uploads'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER

# Allowed file extensions (optional security)
ALLOWED_EXTENSIONS = {'txt', 'pdf', 'png', 'jpg', 'jpeg', 'gif'}

def allowed_file(filename):
    """Check if the file has an allowed extension."""
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

# HTML form template
HTML_FORM = """
<!doctype html>
<title>Upload File</title>
<h1>Upload a File</h1>
<form method="post" enctype="multipart/form-data">
  <input type="file" name="file" required>
  <input type="submit" value="Upload">
</form>
<p>{{ message }}</p>
"""

@app.route('/', methods=['GET', 'POST'])
def upload_file():
    message = ""
    if request.method == 'POST':
        # Check if file part is present
        if 'file' not in request.files:
            message = "No file part in request."
        else:
            file = request.files['file']
            if file.filename == '':
                message = "No file selected."
            elif allowed_file(file.filename):
                filename = secure_filename(file.filename)
                file.save(os.path.join(app.config['UPLOAD_FOLDER'], filename))
                message = f"File '{filename}' uploaded successfully!"
            else:
                message = "File type not allowed."
    return render_template_string(HTML_FORM, message=message)

if __name__ == '__main__':
    app.run(debug=True)


"""

How It Works

Flask Setup: Creates a small web server.
HTML Form: Lets the user choose a file and submit it.
File Validation: Checks for allowed extensions to prevent malicious uploads.
Secure Save: Uses secure_filename to avoid unsafe file names.
Uploads Folder: Files are stored in the uploads/ directory.


Run the App

Install Flask:Bashpip install flask


Save the script as app.py.
Run:Bashpython app.py


Open your browser at http://127.0.0.1:5000.


✅ This is the simplest safe approach for a Python upload form.
If you want, I can also show you a pure CGI-based Python upload form without Flask for environments without frameworks.
Do you want me to give you that CGI version too?
"""
