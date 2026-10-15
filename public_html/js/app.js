var App = {
  init: function () {
    if ($('body').hasClass('js')) {
      this.bindUIActions();
    }
  },

  bindUIActions: function () {
    $('body').on('submit', 'form[data-confirm]', function (event) {
      if (this.hasAttribute('data-video-upload')) { return; }
      var message = $(this).attr('data-confirm');

      if (message && !window.confirm(message)) {
        event.preventDefault();
      }
    });

    $('body').on('submit', 'form[data-video-upload]', function (event) {
      event.preventDefault();
      App.uploadVideo(this);
    });

    $('#playlist-hidden-unlock-button').on('click', function () {
      var password = window.prompt('Hidden password:');

      if (password === null) {
        return;
      }

      if (password === '') {
        window.alert('Hidden password is required.');
        return;
      }

      $('#playlist-hidden-password').val(password);
      $('#playlist-hidden-unlock-form').get(0).submit();
    });
  },

  uploadVideo: async function (form) {
    if (form.uploading) { return; }
    var input = form.querySelector('[name="video_file"]');
    var file = input.files[0];
    var status = form.querySelector('[data-upload-status]');
    var progress = form.querySelector('[data-upload-progress]');
    if (!file || file.size <= 0 || file.size > 734003200) {
      status.textContent = 'Select a video up to 700 MiB.';
      return;
    }
    if (!window.confirm(form.getAttribute('data-confirm'))) { return; }
    form.uploading = true;
    input.disabled = true;
    var button = form.querySelector('[type="submit"]');
    button.disabled = true;
    progress.classList.remove('d-none');
    progress.value = 0;
    var warn = function (event) { event.preventDefault(); event.returnValue = ''; };
    window.addEventListener('beforeunload', warn);
    try {
      var token = '';
      var offset = 0;
      var chunkSize = 8388608;
      while (offset < file.size) {
        var end = Math.min(offset + chunkSize, file.size);
        var data = new FormData();
        data.append('_csrf', form.querySelector('[name="_csrf"]').value);
        data.append('token', token);
        data.append('offset', String(offset));
        data.append('total', String(file.size));
        data.append('filename', file.name);
        data.append('chunk', file.slice(offset, end), 'chunk.bin');
        status.textContent = 'Uploading video: ' + Math.floor(offset * 100 / file.size) + '%';
        var result = await App.sendVideoChunk(form.getAttribute('data-video-upload'), data);
        if (!result.token || result.received !== end) { throw new Error(result.error || 'Unexpected upload response.'); }
        token = result.token;
        offset = end;
        progress.value = offset * 100 / file.size;
      }
      form.querySelector('[name="upload_token"]').value = token;
      // Only the staged token and images enter the final request, never the full video.
      input.disabled = true;
      status.textContent = 'Upload complete. Saving video to the database; please wait…';
      window.removeEventListener('beforeunload', warn);
      HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
      status.textContent = error.message || 'Upload failed. Please try again.';
      input.disabled = false;
      button.disabled = false;
      form.uploading = false;
      window.removeEventListener('beforeunload', warn);
    }
  },

  sendVideoChunk: async function (url, data) {
    for (var attempt = 0; attempt < 3; attempt++) {
      try {
        var response = await fetch(url, { method: 'POST', body: data, credentials: 'same-origin' });
      } catch (error) {
        if (attempt === 2) { throw new Error('Chunk transfer failed after 3 attempts. Check your connection.'); }
        await new Promise(function (resolve) { setTimeout(resolve, 1000); });
        continue;
      }
      if ((response.status >= 500 || response.status === 429) && attempt < 2) {
        await new Promise(function (resolve) { setTimeout(resolve, 1000); });
        continue;
      }
      var result;
      try { result = await response.json(); }
      catch (error) {
        throw new Error('Chunk rejected (HTTP ' + response.status + '). No JSON response; check Network → Response or reload if your login expired.');
      }
      if (!response.ok || result.error) {
        throw new Error('Chunk failed (HTTP ' + response.status + '): ' + (result.error || 'Server rejected the request.'));
      }
      return result;
    }
  }
};
