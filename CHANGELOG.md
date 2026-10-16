# Changelog

All notable changes to this project will be documented in this file.

## [0.1.0] - 2026-10-16

### Added

- Initial Playlist application built on VC Framework with PHP 5.3+ compatibility.
- MySQLi-backed per-user video playlists with add, edit and delete operations.
- Embedded video playback using configurable base URLs and URIs.
- MP4, WebM and Ogg uploads up to 700 MiB using sequential 8 MiB chunks and private temporary staging.
- Storage of uploaded video data across up to six BLOB columns and authenticated byte-range streaming.
- Cover image, up to ten additional images, and per-video notes.
- Public and hidden video entries, hidden-playlist password setup, and lock/unlock controls.
- Sticky, responsive iframe player and playlist interactions.
- User authentication, password changes, CSRF protection and flash messages.
- Optional database table prefix, absolute application URL and subdirectory support.
- Apache URL rewriting, Bootstrap Icons and JavaScript interface behaviors.
