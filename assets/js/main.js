import axios from 'axios';

window.axios = axios.create({
  baseURL: 'https://lostforum.fl0w3rs.dev/alderney/'
});
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

//

import { createToastInterface } from "vue-toastification";

const pluginOptions = {
  timeout: 4000
};

window.toast = createToastInterface(pluginOptions);

window.base_link = 'https://lostforum.fl0w3rs.dev/alderney';



//
(function ($) {
  $.fn.customSerialize = function () {
    var arr = {};

    this.find('input, select, textarea').each((i, e) => {
      let ele = $(e);
      let ele_id = ele.attr('id');
      if (ele_id !== undefined) {
        if (ele.attr('type') != 'file' && ele.attr('type') != 'checkbox' && ele.attr('type') != 'radio') {
          arr[ele_id] = ele.val()
        } else if (ele.attr('type') == 'radio') {
          arr[ele_id] = $('#' + ele_id + ':checked').val()
        } else if (ele.attr('type') == 'checkbox') {
          arr[ele_id] = ele.is(':checked')
        }
      }
    });

    return arr
  };
}(jQuery));

window.adminChangeAnswerIsValid = (id, state) => {
  window.axios.post('/api/admin/answer/change', { id, state }).then((response) => {
    location.reload();
  })
}