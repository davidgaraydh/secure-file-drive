const API_BASE = '/proyectos_repositorio/secure-file-drive/api';

let authToken = localStorage.getItem('authToken');
let currentUsername = localStorage.getItem('username');

// Inicializar aplicación
document.addEventListener('DOMContentLoaded', () => {
    if (authToken) {
        showApp();
        loadFiles();
    } else {
        showLogin();
    }

    // Event listeners
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
    document.getElementById('registerForm').addEventListener('submit', handleRegister);
    document.getElementById('uploadForm').addEventListener('submit', handleUpload);
    document.getElementById('fileInput').addEventListener('change', showFileInfo);
});

function showLogin() {
    document.getElementById('loginSection').style.display = 'block';
    document.getElementById('appSection').style.display = 'none';
    document.getElementById('userInfo').style.display = 'none';
    document.getElementById('loginCard').style.display = 'block';
    document.getElementById('registerCard').style.display = 'none';
    document.getElementById('loginError').style.display = 'none';
    document.getElementById('registerError').style.display = 'none';
}

function showRegister() {
    document.getElementById('loginCard').style.display = 'none';
    document.getElementById('registerCard').style.display = 'block';
    document.getElementById('loginError').style.display = 'none';
    document.getElementById('registerError').style.display = 'none';
}

function showApp() {
    document.getElementById('loginSection').style.display = 'none';
    document.getElementById('appSection').style.display = 'block';
    document.getElementById('userInfo').style.display = 'flex';
    document.getElementById('username').textContent = currentUsername;
}

async function handleLogin(e) {
    e.preventDefault();
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;
    const errorDiv = document.getElementById('loginError');

    try {
        const response = await fetch(`${API_BASE}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json();

        if (response.ok) {
            authToken = data.token;
            currentUsername = data.username;
            localStorage.setItem('authToken', authToken);
            localStorage.setItem('username', currentUsername);
            showApp();
            loadFiles();
            errorDiv.style.display = 'none';
        } else {
            errorDiv.textContent = data.error || 'Error al iniciar sesión';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Error de conexión';
        errorDiv.style.display = 'block';
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const username = document.getElementById('registerUsername').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;
    const passwordConfirm = document.getElementById('registerPasswordConfirm').value;
    const errorDiv = document.getElementById('registerError');

    // Validar que las contraseñas coincidan
    if (password !== passwordConfirm) {
        errorDiv.textContent = 'Las contraseñas no coinciden';
        errorDiv.style.display = 'block';
        return;
    }

    // Validar longitud mínima
    if (password.length < 6) {
        errorDiv.textContent = 'La contraseña debe tener al menos 6 caracteres';
        errorDiv.style.display = 'block';
        return;
    }

    errorDiv.style.display = 'none';

    try {
        const response = await fetch(`${API_BASE}/register.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                username, 
                password,
                email: email || null
            })
        });

        const data = await response.json();

        if (response.ok) {
            errorDiv.style.display = 'none';
            alert('¡Registro exitoso! Ahora puedes iniciar sesión.');
            showLogin();
            // Limpiar formulario
            document.getElementById('registerForm').reset();
        } else {
            errorDiv.textContent = data.error || 'Error al registrar usuario';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Error de conexión';
        errorDiv.style.display = 'block';
    }
}

function logout() {
    localStorage.removeItem('authToken');
    localStorage.removeItem('username');
    authToken = null;
    currentUsername = null;
    showLogin();
}

async function handleUpload(e) {
    e.preventDefault();
    const fileInput = document.getElementById('fileInput');
    const messageDiv = document.getElementById('uploadMessage');

    if (!fileInput.files.length) {
        messageDiv.textContent = 'Por favor selecciona un archivo';
        messageDiv.className = 'message error';
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    messageDiv.textContent = 'Subiendo archivo...';
    messageDiv.className = 'message';

    try {
        const response = await fetch(`${API_BASE}/upload.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`
            },
            body: formData
        });

        const data = await response.json();

        if (response.ok) {
            messageDiv.textContent = 'Archivo subido correctamente';
            messageDiv.className = 'message success';
            fileInput.value = '';
            document.getElementById('fileInfo').textContent = '';
            loadFiles();
        } else {
            messageDiv.textContent = data.error || 'Error al subir el archivo';
            messageDiv.className = 'message error';
        }
    } catch (error) {
        messageDiv.textContent = 'Error de conexión';
        messageDiv.className = 'message error';
    }
}

function showFileInfo(e) {
    const file = e.target.files[0];
    const fileInfo = document.getElementById('fileInfo');
    
    if (file) {
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        fileInfo.textContent = `Archivo: ${file.name} | Tamaño: ${sizeMB} MB | Tipo: ${file.type}`;
    } else {
        fileInfo.textContent = '';
    }
}

async function loadFiles() {
    const filesList = document.getElementById('filesList');
    filesList.innerHTML = '<p class="loading">Cargando archivos...</p>';

    try {
        const response = await fetch(`${API_BASE}/files.php`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        const data = await response.json();

        if (response.ok) {
            displayFiles(data.files || []);
        } else {
            filesList.innerHTML = `<p class="error-message">${data.error || 'Error al cargar archivos'}</p>`;
        }
    } catch (error) {
        filesList.innerHTML = '<p class="error-message">Error de conexión</p>';
    }
}

function displayFiles(files) {
    const filesList = document.getElementById('filesList');

    if (files.length === 0) {
        filesList.innerHTML = '<div class="empty-state"><p>No hay archivos</p><p>Sube tu primer archivo usando el formulario de arriba</p></div>';
        return;
    }

    filesList.innerHTML = files.map(file => `
        <div class="file-item">
            <div class="file-info-item">
                <div class="file-name">${escapeHtml(file.original_name)}</div>
                <div class="file-meta">
                    <span>📅 ${formatDate(file.upload_date)}</span>
                    <span>📦 ${formatFileSize(file.file_size)}</span>
                    <span>🏷️ ${file.extension.toUpperCase()}</span>
                </div>
            </div>
            <div class="file-actions">
                <button class="btn-small btn-share" onclick="generateSignedUrl(${file.id})">🔗 Compartir</button>
                <button class="btn-small btn-download" onclick="downloadFile(${file.id})">⬇️ Descargar</button>
                <button class="btn-small btn-delete" onclick="deleteFile(${file.id})">🗑️ Eliminar</button>
            </div>
        </div>
    `).join('');
}

async function downloadFile(fileId) {
    try {
        // Generar URL firmada y descargar
        const response = await fetch(`${API_BASE}/signed-url.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ file_id: fileId })
        });

        const data = await response.json();

        if (response.ok) {
            window.open(data.signed_url, '_blank');
        } else {
            alert(data.error || 'Error al generar enlace de descarga');
        }
    } catch (error) {
        alert('Error de conexión');
    }
}

async function generateSignedUrl(fileId) {
    const expirationHours = prompt('¿Cuántas horas de validez? (por defecto 24):', '24');
    const hours = parseInt(expirationHours) || 24;

    try {
        const response = await fetch(`${API_BASE}/signed-url.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ 
                file_id: fileId,
                expiration_hours: hours
            })
        });

        const data = await response.json();

        if (response.ok) {
            const url = window.location.origin + data.signed_url;
            navigator.clipboard.writeText(url).then(() => {
                alert('URL copiada al portapapeles:\n' + url);
            }).catch(() => {
                prompt('Copia esta URL:', url);
            });
        } else {
            alert(data.error || 'Error al generar enlace');
        }
    } catch (error) {
        alert('Error de conexión');
    }
}

async function deleteFile(fileId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este archivo?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/delete.php?id=${fileId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        const data = await response.json();

        if (response.ok) {
            loadFiles();
        } else {
            alert(data.error || 'Error al eliminar archivo');
        }
    } catch (error) {
        alert('Error de conexión');
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

