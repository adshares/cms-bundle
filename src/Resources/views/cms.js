import $ from 'jquery'

import 'tinymce'
import 'tinymce/themes/silver/theme'
import 'tinymce/icons/default/icons'
import 'tinymce/plugins/code'

const CMS_NAME = 'cms-name'
const CMS_LOCALE = 'cms-locale'
const BOOTSTRAP_PLUGIN_KEY = 'tAj3ykawOTzO195azIrI398bWt0b3XTV81JV/lbJUIjS3J/JTek+KS1dWnTUdJxQcZETNZtBTotT5aIpXyNRnIiqyT0jpZV9nn3mnkEQPs4='

const content = $('.cms-content')
const saveChangesBtn = $('#cms-save-button')
const originContent = {}
const changedContent = {}
const saveChangesBtnStatus = {
  success: { class: 'btn btn-success btn-sm', message: ' Changes saved', icon: 'bi bi-check-lg' },
  error: { class: 'btn btn-danger btn-sm', message: ' Saving error', icon: 'bi bi-x-lg' },
  default: { class: 'btn btn-primary btn-sm', message: ' Save changes', icon: 'bi bi-send' },
  process: { class: 'btn btn-primary btn-sm', message: ' Saving', icon: 'bi bi-arrow-repeat' },
}

content.each(function (_idx) {
  const contentName = $(this).data(CMS_NAME)
  const contentLocale = $(this).data(CMS_LOCALE)
  const tinymceSelector = `.cms-content[data-cms-name=${contentName}]`

  originContent[contentName] = {
    name: contentName,
    value: $(this).html(),
    locale: contentLocale,
  }

  tinymce.init({
    selector: tinymceSelector,
    inline: true,
    plugins: 'code bootstrap',
    toolbar: [
      'undo redo | bootstrap',
      'cut copy paste | styleselect | alignleft aligncenter alignright alignjustify | bold italic | link image | code | help'
    ],
    contextmenu: 'bootstrap',
    bootstrapConfig: {
      url: '/build/scripts/plugins/bootstrap/',
      iconFont: 'fontawesome5',
      imagesPath: '/uploads',
      enableTemplateEdition: false,
      bootstrapCss: '/build/styles/app.css',
      elements: {
        btn: true,
        icon: true,
        image: true,
        table: true,
        badge: true,
        alert: true,
      },
      key: BOOTSTRAP_PLUGIN_KEY,
    },
    setup: function (editor) {
      editor.on('change', function (_event) {
        if (originContent[contentName].value.replaceAll(/\n|\s/g, '') === editor.getContent().replaceAll(/\n|\s/g, '')) {
          delete changedContent[contentName]
          setButton(saveChangesBtnStatus.default, $.isEmptyObject(changedContent))
        }
        else {
          changedContent[contentName] = {
            name: contentName,
            value: editor.getContent(),
            locale: contentLocale,
          }
          setButton(saveChangesBtnStatus.default, $.isEmptyObject(changedContent))
        }
      })
    }
  })
})

saveChangesBtn.on('click', function () {
  if ($.isEmptyObject(changedContent)) {
    return
  }

  setButton(saveChangesBtnStatus.process, true)

  $.ajax({
    method: 'PATCH',
    url: '/cms/content',
    data: JSON.stringify(changedContent),
    success: function () {
      Object.entries(changedContent).map(element => {
        originContent[element[0]] = element[1]
        delete changedContent[element[0]]
      })
      setButton(saveChangesBtnStatus.success, true)
    },
    error: function () {
      setButton(saveChangesBtnStatus.error, false)
    },
  })
})

function setButton (buttonStatus, disabled) {
  saveChangesBtn
    .attr('disabled', disabled)
    .removeClass()
    .addClass(buttonStatus.class)
    .text(buttonStatus.message)
    .prepend(`<i class="${buttonStatus.icon}"></i>`)
}
