(function () {
  'use strict';

  function addRuleCard() {
    var container = document.getElementById('wcfr-rules');
    if (!container) {
      return;
    }

    var index = container.querySelectorAll('.wcfr-rule-card').length;
    var wrapper = document.createElement('div');
    wrapper.className = 'wcfr-rule-card';
    wrapper.innerHTML = '' +
      '<input type="hidden" name="wcfr_rules[' + index + '][id]" value="" />' +
      '<p><label>Name<br /><input type="text" class="regular-text" name="wcfr_rules[' + index + '][name]" value="" /></label></p>' +
      '<p><label>Strategy<br />' +
      '<select name="wcfr_rules[' + index + '][strategy]">' +
      '<option value="find_replace">Find/Replace</option>' +
      '<option value="wikimedia_thumbnail_roundup">Wikimedia Thumbnail Roundup</option>' +
      '</select></label></p>' +
      '<p><label>Find<br /><textarea rows="3" class="large-text" name="wcfr_rules[' + index + '][find]"></textarea></label></p>' +
      '<p><label>Replace<br /><textarea rows="3" class="large-text" name="wcfr_rules[' + index + '][replace]"></textarea></label></p>' +
      '<p>' +
      '<label><input type="checkbox" name="wcfr_rules[' + index + '][enabled]" value="1" checked="checked" /> Enabled</label> ' +
      '<label><input type="checkbox" name="wcfr_rules[' + index + '][use_regex]" value="1" /> Use regex</label> ' +
      '<label><input type="checkbox" name="wcfr_rules[' + index + '][ignore_case]" value="1" /> Ignore case</label> ' +
      '<label><input type="checkbox" name="wcfr_rules[' + index + '][apply_in_admin]" value="1" /> Apply in admin</label>' +
      '</p>';

    container.appendChild(wrapper);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var addButton = document.getElementById('wcfr-add-rule');
    if (addButton) {
      addButton.addEventListener('click', addRuleCard);
    }
  });
})();
