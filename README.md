# Playlist

Lightweight PHP playlist player for storing and playing iframe-embeddable videos from a database.

Playlist provides a simple mobile-friendly interface with a sticky iframe player and a database-backed video list. Videos can be added, edited and deleted, and can be separated into public and password-protected hidden playlists.

The iframe source is built from a base URL and a video-specific URI, making it suitable for YouTube and other iframe-compatible video sources. Playlist is built on [VC Framework](https://github.com/dsoos1290/vcframework) and is compatible with PHP 5.3 and newer.

## Features

* Database-backed video playlist
* Full-width iframe video player
* Sticky player while scrolling
* Player height limited to 50% of the viewport
* Iframe source built from URL and URI
* Video selection through URL video IDs
* Public and hidden video playlists
* Password-protected hidden playlist
* Lock and unlock controls for hidden videos
* Separate hidden password configuration
* Add new videos
* Edit existing videos
* Delete videos
* Hidden/public setting per video
* Confirmation before destructive or modifying actions
* User authentication
* User password change
* Per-user playlists
* CSRF protection for form actions
* Flash messages
* Mobile-friendly Bootstrap interface
* Bootstrap Icons
* JavaScript interactions through `app.js`
* MySQLi database connection
* Optional database table prefix
* Optional absolute application URL support
* Apache URL rewriting
* Subdirectory installation support
* Built on VC Framework
* PHP 5.3+ compatibility

## Usage

1. Download the latest version: https://github.com/sdaniel1335/playlist/releases/latest
2. Rename `private_html/app/config/db-sample.php` to `db.php`.
3. Open `db.php` and configure your database connection.
4. Import `install.sql` into the configured database.
5. Open the application in your browser.
6. Log in with the default credentials:

    * Username: `admin`
    * Password: `admin`
7. Change the default password after the first login.
8. Optionally configure a separate hidden playlist password from the **Hidden password** page.
9. Add videos by providing a title, iframe URL and URI.

The final iframe source is generated as:

`URL + URI`

For example:

`https://www.youtube.com/embed/` + `VIDEO_ID`

## Requirements

* PHP 5.3 or newer
* MySQL or MariaDB
* MySQLi PHP extension
* Apache with `mod_rewrite`
* JavaScript-enabled browser

## Configuration

Application settings are located in:

`private_html/app/config/app.php`

Database settings are located in:

`private_html/app/config/db.php`

The following database settings are available:

* `DB_HOST`
* `DB_USER`
* `DB_PASS`
* `DB_NAME`
* `DB_CHAR`
* `DB_TIME`
* `DB_PREFIX`

An optional absolute application URL can be configured with `APP_URL`.