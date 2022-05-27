import $ from 'jquery'

import 'tinymce'
import 'tinymce/themes/silver/theme'
import 'tinymce/icons/default/icons'
import 'tinymce/plugins/code'

const content = $('.cms-content')
const saveChangesBtn = $('#cms-save-button')
const prevContent = {}
const editableElements = {}
let elementsToSave = []

content.each(function (_idx) {
  const thisEditableBlock = this
  prevContent[$(thisEditableBlock).attr('data-cms-name')] = {
    name: $(thisEditableBlock).attr('data-cms-name'),
    value: $(thisEditableBlock).html(),
    locale: $(thisEditableBlock).attr('data-cms-locale'),
  }

  tinymce.init({
    selector: `.cms-content[data-cms-name=${$(this).attr('data-cms-name')}]`,
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
      key: 'tAj3ykawOTzO195azIrI398bWt0b3XTV81JV/lbJUIjS3J/JTek+KS1dWnTUdJxQcZETNZtBTotT5aIpXyNRnIiqyT0jpZV9nn3mnkEQPs4=',
    },
    setup: function (editor) {
      editor.on('change', function (_event) {
        editableElements[$(editor.getBody()).attr('data-cms-name')] = {
          name: $(editor.getBody()).attr('data-cms-name'),
          value: editor.getContent(),
          locale: $(editor.getBody()).attr('data-cms-locale')
        }
        elementsToSave = checkChangedElements(prevContent, editableElements)
        saveChangesBtn.attr('disabled', !elementsToSave.length)
      })
    }
  })
})

saveChangesBtn.on('click', function () {
  if (elementsToSave.length) {
    $.ajax({
      method: 'PATCH',
      url: '/cms/content',
      data: JSON.stringify(elementsToSave),
      success: function () {
        elementsToSave.map(element => {
          prevContent[element.name] = element
        })
        elementsToSave.splice(0, elementsToSave.length)
        saveChangesBtn.attr('disabled', true)
      }
    })
  }
})

function checkChangedElements (prevState, newElements) {
  return Object.values(newElements).filter(element => Object.values(prevState).find(
    prevElement => element.name === prevElement.name && element.value.replaceAll(/\s/g, '') !== prevElement.value.replaceAll(/\s/g, '')
  ))
}
