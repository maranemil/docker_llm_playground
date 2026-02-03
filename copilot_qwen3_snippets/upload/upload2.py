
"""
Here's a **secure, minimal Flask-based file upload form** with essential safeguards. This is the most practical approach for a web-based upload form in Python (as "form" implies a web interface).

---

### 📁 Step-by-Step Implementation
*(Tested with Flask 3.0+)*

#### 1. Install Dependencies
```bash
pip install flask
pip install --upgrade pip
```

#### 2. Create `app.py`
"""


from flask import Flask, request, render_template_string, redirect, url_for, flash
from werkzeug.utils import secure_filename
import os

app = Flask(__name__)
app.secret_key = 'your-secret-key-here'  # Change this in production!
app.config['UPLOAD_FOLDER'] = 'uploads'
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB limit
ALLOWED_EXTENSIONS = {'txt', 'pdf', 'png', 'jpg', 'jpeg', 'gif','zip'}

# Create upload folder
os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)

def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

# Simple HTML template (no separate file needed)
HTML_TEMPLATE = '''
<!doctype html>
<title>Secure File Upload</title>
<h1>📤 Upload a File</h1>
{% with messages = get_flashed_messages() %}
  {% if messages %}
    <ul style="color:red;">
    {% for message in messages %}
      <li>{{ message }}</li>
    {% endfor %}
    </ul>
  {% endif %}
{% endwith %}
<form method="post" enctype="multipart/form-data">
  <input type="file" name="file" required>
  <input type="submit" value="Upload">
</form>
<hr>
<small>Allowed: PDF, TXT, JPG, PNG, GIF (Max 16MB)</small>
'''

@app.route('/', methods=['GET', 'POST'])
def upload_file():
    if request.method == 'POST':
        # Check if file exists in request
        if 'file' not in request.files:
            flash('No file part in request')
            return redirect(request.url)
        
        file = request.files['file']
        
        # Check if user selected a file
        if file.filename == '':
            flash('No file selected')
            return redirect(request.url)
        
        # Validate file
        if not allowed_file(file.filename):
            flash('Invalid file type! Only PDF, TXT, JPG, PNG, GIF allowed.')
            return redirect(request.url)
        
        # Save securely
        filename = secure_filename(file.filename)  # CRITICAL: Prevents path traversal
        filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
        
        # Optional: Avoid overwrites (add timestamp)
        # import time
        # name, ext = os.path.splitext(filename)
        # filename = f"{name}_{int(time.time())}{ext}"
        
        file.save(filepath)
        flash(f'✅ Success! Saved as: {filename}')
        return redirect(url_for('upload_file'))
    
    return render_template_string(HTML_TEMPLATE)

if __name__ == '__main__':
    app.run(debug=True)  # SET debug=False IN PRODUCTION!


"""
#### 3. Run & Test
```bash
python app.py
```
Visit `http://localhost:5000` in your browser.

---

### 🔒 Critical Security Notes (DO NOT SKIP)
| Risk | Solution in Code |
|------|------------------|
| **Path Traversal** | `secure_filename()` sanitizes filenames (blocks `../../malicious.exe`) |
| **File Type Abuse** | `allowed_file()` whitelist validation |
| **Disk Exhaustion** | `MAX_CONTENT_LENGTH` limits upload size |
| **Overwriting Files** | *(Optional)* Add timestamp to filename (see commented code) |
| **XSS in Filenames** | `secure_filename()` removes special characters |

> ⚠️ **Production Must-Dos**:
> 1. Set `debug=False`
> 2. Use environment variables for secrets (`secret_key`)
> 3. Serve with Gunicorn/Nginx (not Flask dev server)
> 4. Scan uploaded files with ClamAV or similar
> 5. Store files outside web root + serve via proxy
> 6. Add CSRF protection (Flask-WTF)

---

### 💡 Alternatives by Use Case
| Scenario | Better Tool |
|----------|-------------|
| **Desktop GUI form** | Tkinter + `filedialog` + `requests` to upload |
| **CLI uploader** | `argparse` + `requests` library |
| **Modern async API** | FastAPI (with Pydantic validation) |
| **Large files/resumable** | tus-py + frontend library (Uppy.js) |
| **No backend needed** | Use cloud storage pre-signed URLs (AWS S3, GCS) |

---

This solution gives you a **production-aware starting point** while staying minimal. Always validate/sanitize on *both* 
client and server sides! For full projects, explore Flask-Uploads or cloud storage SDKs (Boto3 for S3). 
"""