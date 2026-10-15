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
## Uploaded videos, images and notes (0.1.0)

New installations: import `install.sql`. Existing installations with the original media columns: run `alter-video-parts.sql` once. If upgrading directly from the original iframe-only schema, run `alter-upload-media.sql` first, then `alter-video-parts.sql`. Adjust the table names for `DB_PREFIX`. Do not rerun migrations already applied.

All media stays in the same `playlist` row. A video uses `video_blob` followed by `video_blob2` through `video_blob6`, with at most **128 MiB per populated column**. Existing single-column videos are preserved and can still be played. Additional columns stay NULL for shorter files. The total upload limit is **700 MiB** (734,003,200 bytes).

Use **Upload video** for MP4, WebM or Ogg. JavaScript sends the file sequentially in **8 MiB HTTP requests**, displays progress, then submits the metadata and images separately. You do not need to split files manually. The full video is staged in the server's private temporary directory and written into the BLOB columns in one transaction; the temporary file is removed after success. Only completed saves appear in the playlist. Upload tokens are tied to the authenticated session/user and expire after 24 hours of inactivity. Starting a new upload in the same session removes the previous temporary file. Abandoned files from expired/lost sessions should be removed by the host's temporary-file cleanup (prefix `playlist-video-`). Failed uploads can be restarted by pressing Upload video again; automatic resume across page reloads is not provided.

Playback uses authenticated byte-range requests across column boundaries without SQL CONCAT or loading the entire video into PHP memory. Browser-supported codecs are required; videos are not transcoded. Uploaded entries have a small file/play icon. Their filename, title, hidden setting, note and images can be edited, but the video bytes cannot be replaced.

Both embedded and uploaded videos support one cover and ten image slots, each up to **10 MiB**, stored in `MEDIUMBLOB` columns. JPEG, PNG, GIF and WebP are supported. Edit forms allow replacement/removal. The list shows a cover hover/focus preview and an image-count tooltip. Notes appear only in add/edit forms. Hidden media requires the owning user's hidden playlist to be unlocked.

### Hosting settings

The 128 MiB PHP POST limit and 256 MiB database packet limit are sufficient for the new transfer/storage sizes. The final form can include eleven 10 MiB images, so keep `post_max_size` at 128M. The supplied `.user.ini` files use:

```ini
upload_max_filesize = 128M
post_max_size = 128M
max_file_uploads = 20
max_input_time = 3600
max_execution_time = 3600
memory_limit = 256M
```

Enable PHP **fileinfo** and provide at least the video's size in free PHP temporary disk space, plus room for image uploads. No 1 GiB `max_allowed_packet` increase is needed: each BLOB parameter is at most 128 MiB, below the known 256 MiB limit. Web-server/proxy request limits must allow the 8 MiB chunks and final image form. PHP-FPM/web-server timeouts must allow the final database save, which can take longer than a chunk request. Database capacity, transaction logs, replication and backups must accommodate the full row. Test an actual 600–700 MB upload and seeking across the 128 MiB boundaries on the hosting server.
