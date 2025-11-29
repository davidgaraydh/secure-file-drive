# Secure File Drive - MVP

A secure file storage system built with pure PHP (no frameworks) that allows users to upload, manage, and share files securely. This is an MVP (Minimum Viable Product) designed for development and testing purposes.

## Features

- ✅ File upload via REST API
- ✅ File validation (size, MIME type, extension)
- ✅ Local disk storage (ready for S3 integration)
- ✅ Temporary signed URLs for secure downloads
- ✅ Automatic link expiration
- ✅ Metadata storage in database
- ✅ Endpoints to list, retrieve, and delete files
- ✅ JWT authentication
- ✅ User registration system
- ✅ Modern and responsive web interface
- ✅ Basic security controls

## Requirements

- PHP >= 7.4
- Apache with mod_rewrite enabled
- MySQL or SQLite database
- Composer (for autoloading)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/yourusername/secure-file-drive.git
cd secure-file-drive
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure the application

```bash
# Copy the example configuration file
cp config/config.example.php config/config.php
```

### 4. Edit `config/config.php`

Update the following settings:

- **Database credentials**: MySQL username, password, database name
- **JWT Secret**: Generate a secure key using `openssl rand -hex 32`
- **Signed URLs Secret**: Generate another secure key using `openssl rand -hex 32`
- **API base URL**: Adjust according to your server configuration
- **File validation**: Configure max size and allowed file types

### 5. Set up permissions

```bash
chmod -R 755 storage/
```

### 6. Initialize the database

```bash
php install.php
```

This will create the necessary database tables:
- `users` - User accounts
- `files` - File metadata
- `signed_urls` - Temporary download links

### 7. Access the application

Open your browser and navigate to:
```
http://localhost/proyectos_repositorio/secure-file-drive/
```

## Project Structure

```
secure-file-drive/
├── api/                 # API endpoints
│   ├── index.php       # Main router
│   ├── login.php       # Authentication
│   ├── register.php    # User registration
│   ├── upload.php      # File upload
│   ├── files.php       # List files
│   ├── file.php        # Get file info
│   ├── delete.php      # Delete file
│   ├── signed-url.php  # Generate signed URL
│   └── download.php    # Download with token
├── assets/             # Frontend assets
│   ├── css/
│   └── js/
├── config/             # Configuration
│   ├── config.example.php  # Example config (safe to commit)
│   └── config.php          # Your config (NEVER commit)
├── cron/               # Maintenance scripts
│   └── cleanup.php     # Clean expired tokens
├── src/                # PHP source code
│   ├── Auth/          # JWT authentication
│   ├── Database/      # Database management
│   ├── Services/      # Business logic
│   ├── Storage/       # Storage abstraction
│   └── Validation/    # File validation
├── storage/           # File storage and database
│   └── files/         # Uploaded files (not in git)
├── vendor/            # Composer dependencies
├── .htaccess         # Apache configuration
├── composer.json
└── index.html        # Web interface
```

## API Endpoints

### Authentication

**POST /api/register.php**
```json
{
  "username": "newuser",
  "password": "securepassword",
  "email": "user@example.com"
}
```

**POST /api/login.php**
```json
{
  "username": "newuser",
  "password": "securepassword"
}
```
Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "username": "newuser"
}
```

### File Operations

**POST /api/upload.php**
- Headers: `Authorization: Bearer {token}`
- Body: `multipart/form-data` with `file` field

**GET /api/files.php?limit=50&offset=0**
- Headers: `Authorization: Bearer {token}`
- Returns list of user's files

**GET /api/file.php?id={file_id}**
- Headers: `Authorization: Bearer {token}`
- Returns file metadata

**DELETE /api/delete.php?id={file_id}**
- Headers: `Authorization: Bearer {token}`
- Deletes file and metadata

### Signed URLs

**POST /api/signed-url.php**
- Headers: `Authorization: Bearer {token}`
- Body:
```json
{
  "file_id": 1,
  "expiration_hours": 24,
  "max_accesses": null
}
```
- Returns a temporary download URL

**GET /api/download.php?token={token}**
- No authentication required (uses signed token)
- Downloads the file

## Configuration

### File Validation

Edit `config/config.php` to adjust:
- `max_size`: Maximum file size in bytes (default: 50MB)
- `allowed_types`: Allowed MIME types
- `allowed_extensions`: Allowed file extensions

### Signed URLs

- `expiration_hours`: Default validity period (default: 24 hours)
- `secret_key`: Secret key for signing URLs (change in production!)

### Storage

Currently configured for local storage. To use S3:
1. Change `storage.type` to `'s3'`
2. Configure credentials in `storage.s3`
3. Implement `S3Storage.php` following `StorageInterface`

### CORS

In production, restrict CORS origins:
```php
'cors_origins' => ['https://yourdomain.com']
```

## Maintenance

### Cleanup Expired Tokens

Set up a cron job to run periodically:

**Linux/Mac:**
```bash
*/30 * * * * php /path/to/project/cron/cleanup.php >> /path/to/project/storage/cleanup.log 2>&1
```

**Windows (Task Scheduler):**
1. Open Task Scheduler
2. Create basic task
3. Set to run every 30 minutes
4. Action: Start program
5. Program: `php.exe`
6. Arguments: `C:\path\to\project\cron\cleanup.php`

## Security Considerations

### ⚠️ Before Using in Production

This is an MVP. Before deploying to production, you MUST implement:

1. **Rate Limiting**
   - Implement request limits per IP
   - Protection against brute force attacks
   - Upload limits per user

2. **HTTPS Only**
   - Never use without SSL/TLS
   - Configure HSTS headers
   - Redirect HTTP to HTTPS

3. **Restrictive CORS**
   - Don't use `['*']` in production
   - Specify allowed domains explicitly

4. **File Protection**
   - Ensure `storage/` is not publicly accessible
   - Validate all file access
   - Consider antivirus scanning

5. **Enhanced Input Validation**
   - Sanitize all inputs
   - Stricter MIME type validation
   - Protection against path traversal

6. **Logging and Monitoring**
   - Log failed access attempts
   - Monitor suspicious activity
   - Security alerts

7. **Backups**
   - Automated database backups
   - File storage backups
   - Disaster recovery plan

### Security Checklist

- [ ] `config/config.php` is NOT in the repository (it's in `.gitignore`)
- [ ] All secret keys have been changed from defaults
- [ ] HTTPS is configured and working
- [ ] CORS is restricted to specific domains
- [ ] Rate limiting is implemented
- [ ] `storage/` directory is not publicly accessible
- [ ] File permissions are correctly configured
- [ ] Regular backups are being performed
- [ ] Logging system is active
- [ ] Security audit has been performed

### Generate Secure Keys

```bash
# For JWT Secret
openssl rand -hex 32

# For Signed URLs Secret
openssl rand -hex 32
```

### Apache Configuration

Ensure the `storage/` directory is not accessible:

```apache
<Directory "/path/to/project/storage">
    Require all denied
</Directory>
```

### Database Security

- Use a MySQL user with minimal required permissions
- Don't use the `root` user in production
- Enable SSL for database connections if possible

## Before Pushing to GitHub

### ⚠️ Critical Security Steps

1. **Verify `config/config.php` is ignored:**
```bash
git check-ignore config/config.php
# Should output: config/config.php
```

2. **Check what will be committed:**
```bash
git status
```

3. **Files that MUST NOT be committed:**
- ❌ `config/config.php` (contains real credentials)
- ❌ `storage/database.sqlite` or any `.db` files
- ❌ Files in `storage/files/`
- ❌ Any `.log` files

4. **Files that SHOULD be committed:**
- ✅ `config/config.example.php` (no credentials)
- ✅ All source code
- ✅ `README.md`, `.gitignore`, `.gitattributes`

5. **If you accidentally committed credentials:**
   - Change ALL credentials immediately
   - Clean Git history (see Git documentation)
   - Consider the repository compromised

## Troubleshooting

### Database Connection Error

- Verify MySQL credentials in `config/config.php`
- Ensure MySQL service is running
- Check database exists: `CREATE DATABASE secure_file_drive;`

### File Upload Fails

- Check `storage/files/` directory permissions
- Verify `upload_max_filesize` in `php.ini`
- Check `post_max_size` in `php.ini`

### Authentication Not Working

- Verify JWT secret is set in `config/config.php`
- Check token expiration settings
- Ensure database tables are created (`php install.php`)