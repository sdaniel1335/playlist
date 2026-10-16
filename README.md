# Playlist

Lightweight PHP video playlist manager built on [VC Framework](https://github.com/dsoos1290/vcframework) for PHP 5.3 and newer.

Playlist manages per-user collections of embedded and uploaded videos, with a sticky player, optional password-protected hidden playlists, and image attachments.

## Features

* Per-user video playlists with add, edit and delete actions.
* Embedded videos using a base iframe URL and video-specific URI.
* MP4, WebM and Ogg video uploads (up to 700 MiB), sent in 8 MiB chunks and stored across database BLOB columns.
* Authenticated byte-range playback of uploaded videos; no server-side transcoding.
* Full-width, sticky video player with a maximum height of 50% of the viewport.
* Public and password-protected hidden videos, with lock/unlock controls and a separate hidden-playlist password.
* One cover image and up to ten additional images per video (10 MiB per image), plus private notes in edit forms.
* User login, password changes, CSRF protection and flash messages.
* Responsive interface, Bootstrap Icons and JavaScript interactions.
* MySQLi, optional database table prefixes, Apache URL rewriting and subdirectory support.
* No Composer dependency; PHP 5.3+ compatible.

## Requirements

* PHP 5.3+ with MySQLi and fileinfo extensions.
* MySQL or MariaDB with sufficient storage and packet limits for uploaded media.
* Apache with `mod_rewrite`.
* JavaScript-enabled browser and browser-supported video codecs.
* Writable PHP temporary directory with enough space for the entire staged video.

## Installation

1. Download the [latest release](https://github.com/sdaniel1335/playlist/releases/latest) or clone the repository.
2. Rename `private_html/app/config/db-sample.php` to `private_html/app/config/db.php` and configure the database.
3. Import `install.sql` into the configured database (new installations only).
4. Open the application in your browser and sign in with `admin` / `admin`.
5. **Immediately change the default password** and configure a hidden-playlist password if needed.
6. Add an embedded video by specifying its title, base URL and URI, or use the upload form for a local video file.

**Security:** Change the default credentials before exposing the application to the internet. Keep `private_html` inaccessible to direct HTTP requests and set `APP_ENV` appropriately for production.

## Configuration

Application configuration: `private_html/app/config/app.php` (`APP_URL` is optional).

Database configuration: `private_html/app/config/db.php` (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_CHAR`, `DB_TIME`, `DB_PREFIX`).

Embedded video sources are formed by concatenating `URL + URI`, for example `https://www.youtube.com/embed/` and `VIDEO_ID`.

## Media storage and upload limits

* Video files: MP4, WebM or Ogg, maximum **700 MiB** per file.
* Uploads: sequential **8 MiB** HTTP chunks; a complete file is staged in PHP's temporary directory before the database transaction.
* Database storage: up to six video BLOB columns, at most **128 MiB** per populated column.
* Images: one cover and ten additional JPEG, PNG, GIF or WebP images, each up to **10 MiB**.
* Uploaded video content cannot be replaced through the edit form; metadata and images can be edited.
* Upload sessions expire after 24 hours of inactivity; no automatic resume across page reloads.
* Playback supports byte-range requests, but does not transcode unsupported codecs.

Suggested PHP settings (the hosting environment may require additional adjustments):

```ini
upload_max_filesize = 128M
post_max_size = 128M
max_file_uploads = 20
max_input_time = 3600
max_execution_time = 3600
memory_limit = 256M
```

The web server must permit the chunk and image requests; the database and PHP timeouts must permit the final save. Plan for sufficient temporary disk space, database storage and backups. Test large uploads and seeking on the target host.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the release history.
