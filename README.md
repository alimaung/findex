# QNAP TS-251+ File Index Browser

A modern, responsive file browser for your QNAP TS-251+ NAS server that allows you to navigate through your `/Web` directory structure with an intuitive interface.

## Features

- 🔐 **Secure Login System**: Password-protected access with session management
- 📁 **Directory Navigation**: Browse through folders with breadcrumb navigation
- 🔍 **Search Functionality**: Filter files and folders by name
- 👁️ **Dual View Modes**: Switch between list and grid views
- 📱 **Responsive Design**: Works on desktop, tablet, and mobile devices
- 🎨 **File Type Icons**: Visual icons for different file types (images, videos, documents, etc.)
- 📊 **File Information**: Shows file sizes, modification dates, and permissions
- 🔒 **Advanced Security**: Login protection, session timeout, rate limiting, and path traversal protection
- 🚪 **Auto-Logout**: Automatic logout after inactivity

## Installation

1. **Upload Files**: Copy all files to your QNAP's `/Web` directory (usually `/share/Web/`):
   - `login.php` (Login page)
   - `index.php` (Main file browser)
   - `browse.php` (Backend directory reader)
   - `auth.php` (Authentication system)
   - `logout.php` (Logout handler)

2. **Set Permissions**: Ensure PHP files have execute permissions:
   ```bash
   chmod 755 *.php
   ```

3. **Configure Login Credentials**: Edit `login.php` and change the default passwords:
   ```php
   $VALID_USERS = [
       'admin' => 'your_secure_password_here',
       'user' => 'another_password_here'
   ];
   ```

4. **PHP Requirements**: Make sure PHP is enabled on your QNAP Web Server:
   - Go to QNAP Control Panel
   - Navigate to Applications > Web Server
   - Enable PHP and restart the web server

## Usage

1. Open your web browser and navigate to your QNAP's IP address
2. You'll be presented with a secure login page
3. Enter your username and password (as configured in `login.php`)
4. After successful login, the file browser will load showing your `/Web` directory contents
5. Click on folders to navigate deeper into the directory structure
6. Use the breadcrumb navigation to go back to parent directories
7. Toggle between list and grid views using the toolbar buttons
8. Use the search box to filter files and folders
9. Click the logout button when finished to securely end your session

## File Structure

```
/Web/
├── login.php           # Secure login page
├── index.php           # Main file browser interface (protected)
├── browse.php          # Backend script for directory reading (protected)
├── auth.php            # Authentication system
├── logout.php          # Logout handler
├── test.php            # PHP test script (optional)
└── README.md          # This documentation file
```

## Security Features

- **Login Authentication**: Username/password protection with secure session management
- **Session Timeout**: Automatic logout after 30 minutes of inactivity
- **Rate Limiting**: Protection against brute force attacks (5 attempts per 15 minutes)
- **Path Traversal Protection**: Prevents access to directories outside the `/Web` folder
- **Input Sanitization**: All user inputs are sanitized to prevent security issues
- **Access Control**: Only authenticated users can access files and directories
- **Secure Logout**: Complete session destruction on logout
- **Error Handling**: Graceful error handling for permission and authentication issues

## Customization

### Changing the Base Directory

To browse a different directory, modify the `$allowedPaths` array in `browse.php`:

```php
$allowedPaths = [
    '/share/YourDirectory',
    '/YourDirectory',
    './YourDirectory'
];
```

### Styling

The interface uses modern CSS with a responsive design. You can customize colors and layout by modifying the CSS in `index.html`.

## Troubleshooting

### Common Issues

1. **"Directory not found" error**:
   - Check that the files are in the correct `/Web` directory
   - Verify directory permissions

2. **PHP errors**:
   - Ensure PHP is enabled in QNAP Web Server settings
   - Check PHP error logs in QNAP system logs

3. **Permission denied**:
   - Verify file permissions: `chmod 755 browse.php`
   - Check that the web server user has read access to directories

### Browser Compatibility

- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 12+
- ✅ Edge 79+

## Technical Details

- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP 7.0+
- **AJAX**: Fetch API for asynchronous directory loading
- **Security**: Input validation and path traversal prevention
- **Responsive**: CSS Grid and Flexbox for modern layouts

## License

This project is open source and available under the MIT License.

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify your QNAP system meets the requirements
3. Check QNAP system logs for any error messages 