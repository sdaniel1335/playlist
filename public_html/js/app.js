var App = {
  init: function () {
    if ($('body').hasClass('js')) {
      this.bindUIActions();
    }
  },

  bindUIActions: function () {
    $('body').on('submit', 'form[data-confirm]', function (event) {
      var message = $(this).attr('data-confirm');

      if (message && !window.confirm(message)) {
        event.preventDefault();
      }
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
  }
};
