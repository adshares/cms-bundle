import $ from 'jquery'

/* Import TinyMCE */
import tinymce from 'tinymce'

/* Default icons are required. After that, import custom icons if applicable */
import 'tinymce/icons/default'

/* Required TinyMCE components */
import 'tinymce/themes/silver'
import 'tinymce/models/dom'

/* Import plugins */
import 'tinymce/plugins/advlist'
import 'tinymce/plugins/code'
import 'tinymce/plugins/link'
import 'tinymce/plugins/lists'
import '../plugins/bootstrap/plugin'

window.jQuery = $

import('@tinymce/tinymce-jquery/dist/tinymce-jquery')

const CMS_NAME = 'cms-name'
const CMS_LOCALE = 'cms-locale'
const BOOTSTRAP_PLUGIN_KEY = 'tAj3ykawOTzO195azIrI398bWt0b3XTV81JV/lbJUIjS3J/JTek+KS1dWnTUdJxQcZETNZtBTotT5aIpXyNRnIiqyT0jpZV9nn3mnkEQPs4='

const editorOptions = {
  plugins: 'code link lists advlist bootstrap',
  menubar: 'file edit insert view format',
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
  convert_urls: false,
}

$(document).ready(function () {
  initRollbackButton($('.cms-rollback-button'))
  initDeleteButton($('.cms-delete-button'))
  initContentEditor($('.cms-content'))
  initArticleEditor($('.cms-article-form'))
})

function initRollbackButton (button) {
  button.click(function () {
    const locale = $(this).data('locale')
    const state = $(this).data('state')
    const ref = $(this).data('ref') + (window.location.hash ? window.location.hash : '')
    const action = $(this).data('action')

    const changes = []
    Object.entries(state).forEach(([name, version]) => {
      changes.push({
        name,
        version: parseInt(version),
        locale,
      })
    })

    $.ajax({
      method: 'PATCH',
      url: action,
      data: JSON.stringify(changes),
      success: function () {
        window.location.href = ref
      },
      error: function (data) {
        console.error(data)
      },
    })
  })
}

function initDeleteButton (button) {
  button.click(function () {

    if (!confirm('Are you sure you want to delete the entity?')) {
      return
    }

    const action = $(this).data('action')
    const ref = $(this).data('ref')

    $.ajax({
      method: 'DELETE',
      url: action,
      success: function (data) {
        window.location.href = ref
      },
      error: function (data) {
        console.error(data)
      },
    })
  })
}

function initContentEditor (content) {
  if (0 === content.length) {
    return
  }
  const saveChangesBtn = $('.cms-save-button')
  const historyBtn = $('.cms-history-button')
  const originContent = {}
  const changedContent = {}
  const saveChangesBtnStatus = {
    success: { class: 'btn btn-success btn-sm', message: ' Changes saved', icon: 'bi bi-check-lg' },
    error: { class: 'btn btn-danger btn-sm', message: ' Saving error', icon: 'bi bi-x-lg' },
    default: { class: 'btn btn-primary btn-sm', message: ' Save changes', icon: 'bi bi-send' },
    process: { class: 'btn btn-primary btn-sm', message: ' Saving', icon: 'bi bi-arrow-repeat' },
  }

  $('body > *:not(.cms-toolbar):not(.sf-toolbar)').find('a,button,input[type="submit"]').click(function (event) {
    event.preventDefault()
    event.stopImmediatePropagation()
  })

  if (saveChangesBtn.length) {
    $(window).on('beforeunload', function () {
      if (!saveChangesBtn.is(':disabled')) {
        return 'Changes you made may not be saved'
      }
    })
  }

  const names = []
  content.each(function (_idx) {
    const contentName = $(this).data(CMS_NAME)
    const contentLocale = $(this).data(CMS_LOCALE)
    const tinymceSelector = `.cms-content[data-cms-name=${contentName}]`

    names.push(contentName)

    originContent[contentName] = {
      name: contentName,
      value: $(this).html(),
      locale: contentLocale,
    }

    tinymce.init({
      ...editorOptions,
      selector: tinymceSelector,
      inline: true,
      menu: {
        custom: { title: `#${contentName}`, items: 'copyContentName' }
      },
      menubar: `${editorOptions.menubar} custom`,
      setup: function (editor) {
        editor.ui.registry.addMenuItem('copyContentName', {
          text: 'Copy name',
          onAction: () => navigator.clipboard.writeText(contentName)
        })
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

  if (names.length > 0) {
    historyBtn
      .attr('href', `${historyBtn.attr('href')}&names[]=${names.join('&names[]=')}`)
      .removeClass('disabled')
  }

  saveChangesBtn.on('click', function () {
    if ($.isEmptyObject(changedContent)) {
      return
    }

    setButton(saveChangesBtnStatus.process, true)

    $.ajax({
      method: 'PATCH',
      url: saveChangesBtn.data('action'),
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
}

function initArticleEditor (content) {
  if (0 === content.length) {
    return
  }

  tinymce.init({
    ...editorOptions,
    selector: 'textarea#article_content',
    setup: function (editor) {
      editor.on('change', function () {
        tinymce.triggerSave()
      })
    }
  })

  $('select#article_type').change(function () {
    const articleNo = $('input#article_no')
    const articleStartAt = $('input#article_startAt')
    const articleEndAt = $('input#article_endAt')
    const articleImage = $('input#article_image')
    const articleVideo = $('input#article_video')

    const isArticle = 'article' === $(this).val() || 'general' === $(this).val() || 'tutorial' === $(this).val() || 'short' === $(this).val()
    const isFAQ = 'faq' === $(this).val()
    const isTerm = 'term' === $(this).val()

    if (isArticle || isTerm) {
      articleNo.attr('disabled', true).data('val', articleNo.val()).val(null)
    } else {
      articleNo.attr('disabled', false).val(articleNo.val() || articleNo.data('val'))
    }
    if (isFAQ || isTerm) {
      articleStartAt.attr('disabled', true).data('val', articleStartAt.val()).val(null)
    } else {
      articleStartAt.attr('disabled', false).val(articleStartAt.val() || articleStartAt.data('val'))
    }
    if (isArticle || isFAQ || isTerm) {
      articleEndAt.attr('disabled', true).data('val', articleEndAt.val()).val(null)
    } else {
      articleEndAt.attr('disabled', false).val(articleEndAt.val() || articleEndAt.data('val'))
    }
    if (isFAQ || isTerm) {
      articleImage.attr('disabled', true)
      articleVideo.attr('disabled', true).data('val', articleVideo.val()).val(null)
    } else {
      articleImage.attr('disabled', false)
      articleVideo.attr('disabled', false).val(articleVideo.val() || articleVideo.data('val'))
    }
  })

  const maxNameLength = 64
  $('input#article_title').keyup(function () {
    let name = $(this).val()
    name = name.trim().toLowerCase().replace(/[^0-9a-z]/g, '-')
      .replace(/-+/g, '-').replace(/(^-+|-+$)/g, '')
    if (name.length > maxNameLength) {
      let shortName = ''
      name.split('-').some(part => {
        if ((shortName + part).length > maxNameLength) {
          return true
        }
        shortName += `${part}-`
      })
      name = shortName.substring(0, shortName.length - 1)
    }
    $('input#article_name').val(name)
  })

  const ytRegExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/
  $('input#article_video').keyup(function () {
    let url = $(this).val()
    const match = url.match(ytRegExp)
    if (match && 11 === match[7].length) {
      $(this).val(match[7])
    }
  })

}
