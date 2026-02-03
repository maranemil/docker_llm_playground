"""
Got it — you want to create a simple file upload form using only native Python classes (no external frameworks like Flask, Django, or Requests).
We can do this using Python’s built-in http.server and cgi modules.
Here’s a complete, runnable example:

Python
"""

#!/usr/bin/env python3
import http.server
import cgi
import os

# Directory where uploaded files will be stored
UPLOAD_DIR = "uploads"

# Ensure the upload directory exists
os.makedirs(UPLOAD_DIR, exist_ok=True)

class SimpleUploadHandler(http.server.BaseHTTPRequestHandler):
    def _render_form(self):
        """Send a simple HTML upload form."""
        self.send_response(200)
        self.send_header("Content-type", "text/html")
        self.end_headers()
        self.wfile.write(b"""
            <html>
            <head><title>Upload File</title></head>
            <body>
                <h2>Upload a File</h2>
                <form enctype="multipart/form-data" method="post">
                    <input type="file" name="file" required>
                    <input type="submit" value="Upload">
                </form>
            </body>
            </html>
        """)

    def do_GET(self):
        """Serve the upload form."""
        self._render_form()
        """
        fn = 'html/' + self.path[1:]
        fType = fn.split('.')[-1].lower()
        if (fType == 'html'):
            ctype = 'text/html'
        elif (fType == 'css'):
            ctype = 'text/css'
        elif (fType == 'js'):
            ctype = 'text/javascript'
        elif (fType == 'json'):
            ctype = 'application/json'
        print(ctype)
        self.send_header('Content-type', ctype)
        self.send_response(200)
        self.end_headers()
        """

    def do_POST(self):
        """Handle file upload."""
        try:
            # Parse the form data posted
            form = cgi.FieldStorage(
                fp=self.rfile,
                headers=self.headers,
                environ={
                    'REQUEST_METHOD': 'POST',
                    'CONTENT_TYPE': self.headers['Content-Type'],
                }
            )

            # Get the uploaded file
            uploaded_file = form['file']
            if uploaded_file.filename:
                filename = os.path.basename(uploaded_file.filename)
                filepath = os.path.join(UPLOAD_DIR, filename)

                # Save file to disk
                with open(filepath, 'wb') as f:
                    f.write(uploaded_file.file.read())

                self.send_response(200)
                self.send_header("Content-type", "text/html")
                self.end_headers()
                self.wfile.write(f"<h3>File '{filename}' uploaded successfully!</h3>".encode())
            else:
                self.send_error(400, "No file uploaded.")

        except Exception as e:
            self.send_error(500, f"Error processing upload: {e}")

# Run the server
if __name__ == "__main__":
    PORT = 8888
    server_address = ("", PORT)
    httpd = http.server.HTTPServer(server_address, SimpleUploadHandler)
    print(f"Serving on http://localhost:{PORT}")
    httpd.serve_forever()


"""
How It Works

BaseHTTPRequestHandler is subclassed to handle GET (form display) and POST (file upload).
cgi.FieldStorage parses the incoming multipart form data.
Uploaded files are saved to the uploads/ directory.
No external libraries — pure Python standard library.


Running It

Save the script as upload_server.py.
Run:Bashpython3 upload_server.py


Open your browser at:
http://localhost:8888
Select a file and click Upload.


Notes & Edge Cases

Handles missing file gracefully.
Uses os.path.basename to prevent directory traversal attacks.
Works for small to medium files; for large files, you’d want streaming.
cgi is deprecated in Python 3.11+ — for future-proofing, you might replace it with email.parser or manual multipart parsing.


If you want, I can give you a modern version without cgi that works in Python 3.12+ and is more secure.
Do you want me to prepare that?
"""
