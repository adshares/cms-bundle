import $ from 'jquery'

/* Import TinyMCE */
import tinymce from 'tinymce'

/* Default icons are required. After that, import custom icons if applicable */
import 'tinymce/icons/default'

/* Required TinyMCE components */
import 'tinymce/themes/silver'
import 'tinymce/models/dom'

/* Import plugins */
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/code'
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import './plugins/bootstrap/plugin'

import '@tinymce/tinymce-jquery/dist/tinymce-jquery'

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
    plugins: 'code bootstrap link lists advlist',
    toolbar: [
      'undo redo | cut copy paste | bootstrap link',
      'styles | alignleft aligncenter alignright alignjustify | bold italic strikethrough | bullist numlist '
    ],
    contextmenu: 'bootstrap',
    // file_picker_types: 'file image media',
    bootstrapConfig: {
      bootstrapColumns: 24,
      url: '/build/scripts/plugins/bootstrap/',
      iconFont: 'bootstrap-icons',
      imagesPath: '/uploads',
      key: BOOTSTRAP_PLUGIN_KEY,
      enableTemplateEdition: false,
      bootstrapCss: '/build/styles/cms.css',
      elements: {
        btn: true,
        icon: true,
        image: true,
        table: true,
        badge: true,
        alert: true,
      },

    },
    formats: {
      display1: { selector: '*', classes: 'display-1' },
      display2: { selector: '*', classes: 'display-2' },
      display3: { selector: '*', classes: 'display-3' },
      display4: { selector: '*', classes: 'display-4' },
      display5: { selector: '*', classes: 'display-5' },
      display6: { selector: '*', classes: 'display-6' },
      fwlighter: { selector: '*', classes: 'fw-lighter' },
      fwlight: { selector: '*', classes: 'fw-light' },
      fwnormal: { selector: '*', classes: 'fw-normal' },
      fwbold: { selector: '*', classes: 'fw-bold' },
      fwbolder: { selector: '*', classes: 'fw-bolder' },
    },
    styles: {
      alignleft: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes: 'text-start' },
      aligncenter: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes: 'text-center' },
      alignright: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes: 'text-end' },
      alignjustify: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes: 'text-justify' },
      bold: { inline: 'strong' },
      italic: { inline: 'em' },
      underline: { inline: 'u' },
      sup: { inline: 'sup' },
      sub: { inline: 'sub' },
      strikethrough: { inline: 'del' },
    },
    style_formats: [
      {
        title: 'Displays', items: [
          { title: 'Display 1', format: 'display1' },
          { title: 'Display 2', format: 'display2' },
          { title: 'Display 3', format: 'display3' },
          { title: 'Display 4', format: 'display4' },
          { title: 'Display 5', format: 'display5' },
          { title: 'Display 6', format: 'display6' }
        ]
      },
      {
        title: 'Font weight', items: [
          { title: 'Lighter', format: 'fwlighter' },
          { title: 'Light', format: 'fwlight' },
          { title: 'Normal', format: 'fwnormal' },
          { title: 'Bold', format: 'fwbold' },
          { title: 'Bolder', format: 'fwbolder' },
        ]
      },
    ],
    style_formats_merge: true,
    style_formats_autohide: true,
    setup: function (editor) {
      editor.on('change', function (_event) {
        if (originContent[contentName].value.replaceAll(/\n|\s{2,}/g, '').replaceAll(/(<\w+>)\s/g, '$1').replaceAll(/\s(<\/\w+>)/g, '$1') === editor.getContent().replaceAll(/\n|\s{2,}/g, '').replaceAll(/(<\w+>)\s/g, '$1').replaceAll(/\s(<\/\w+>)/g, '$1')) {
          delete changedContent[contentName]
          setButton(saveChangesBtnStatus.default, $.isEmptyObject(changedContent))
        } else {
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
