const $content = jQuery('#post-body-content')
const $form = $content.find('form')

if ($form.length) {
  $form.on('submit', function (e) {
    const makeAjax = function (data) {
      jQuery.ajax({
        url: window.ajaxurl,
        type: "POST",
        'data': data,
        enctype: 'application/x-www-form-urlencoded',
        processData: false,
        contentType: false
      })
        .always(function () {
          $content.find('.notice').remove()
        })
        .done(function (response) {
          if (response.data.complete === true) {
            $content
              .prepend(`<div class="notice notice-success is-dismissible">
                <p><strong>Success!</strong><br>Deleting your selected sizes affected a total of ${response.data.total} images.</p>
                <p>This page will automatically refresh.</p>
              </div>`)

            setTimeout(function () {
              window.location.reload()
            }, 2000)
          } else {
            $content
              .prepend(`<div class="notice notice-info is-dismissible">
                <p><strong>Deleting Images:</strong><br>Deleted ${response.data.complete} of ${response.data.total} images</p>
              </div>`)

            if (data.has('total') === false) {
              data.append('total', response.data.total)
            }

            data.delete('completed')
            data.append('completed', response.data.complete)

            makeAjax(data)
          }
        })
        .fail(function (jqXHR) {
          $content.prepend(`<div class="notice notice-error is-dismissible"><p>${jqXHR.responseJSON.data}</p></div>`)
        })
    }

    const data = new FormData(this)

    if (data.get('action') === 'bulk-delete' || data.get('action2') === 'bulk-delete') {
      e.preventDefault()

      data.delete('action')
      data.delete('action2')
      data.append('action', 'delete_dynamic_image_sizes')
      makeAjax(data)
    }
  })
}