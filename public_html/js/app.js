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
      var password = window.prompt(window.AppLang.LANG_HIDDEN_PASSWORD_TEXT);

      if (password === null) {
        return;
      }

      if (password === '') {
        window.alert(window.AppLang.LANG_HIDDEN_PASSWORD_IS_REQUIRED);
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
      status.textContent = window.AppLang.LANG_SELECT_A_VIDEO_UP_TO_700_MIB;
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
        status.textContent = window.AppLang.LANG_UPLOADING_VIDEO + Math.floor(offset * 100 / file.size) + '%';
        var result = await App.sendVideoChunk(form.getAttribute('data-video-upload'), data);
        if (!result.token || result.received !== end) { throw new Error(result.error || window.AppLang.LANG_UNEXPECTED_UPLOAD_RESPONSE); }
        token = result.token;
        offset = end;
        progress.value = offset * 100 / file.size;
      }
      form.querySelector('[name="upload_token"]').value = token;
      // Only the staged token and images enter the final request, never the full video.
      input.disabled = true;
      status.textContent = window.AppLang.LANG_UPLOAD_COMPLETE_SAVING_VIDEO_TO_THE_DATABASE_PLEASE_WAIT;
      window.removeEventListener('beforeunload', warn);
      HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
      status.textContent = error.message || window.AppLang.LANG_UPLOAD_FAILED_PLEASE_TRY_AGAIN;
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
        if (attempt === 2) { throw new Error(window.AppLang.LANG_CHUNK_TRANSFER_FAILED_AFTER_3_ATTEMPTS_CHECK_YOUR_CONNECTION); }
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
        throw new Error(window.AppLang.LANG_CHUNK_REJECTED_HTTP + response.status + window.AppLang.LANG__NO_JSON_RESPONSE_CHECK_NETWORK_RESPONSE_OR_RELOAD_IF_YOUR_LOGIN_EXPIRED);
      }
      if (!response.ok || result.error) {
        throw new Error(window.AppLang.LANG_CHUNK_FAILED_HTTP + response.status + '): ' + (result.error || window.AppLang.LANG_SERVER_REJECTED_THE_REQUEST));
      }
      return result;
    }
  }
};
