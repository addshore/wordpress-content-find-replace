(function () {
  'use strict';

  /**
   * Toggle visible fields based on the chosen strategy.
   *
   * @param {HTMLSelectElement} select
   */
  function applyStrategyToggle(select) {
    var card = select.closest('.wcfr-rule-card');
    if (!card) { return; }
    var isPreset = select.value === 'wikimedia_thumbnail_roundup';
    var presetInfo = card.querySelector('.wcfr-preset-info');
    var frFields   = card.querySelector('.wcfr-rule-fields-fr');
    var frFlags    = card.querySelector('.wcfr-rule-flags');
    if (presetInfo) { presetInfo.classList.toggle('wcfr-hidden', !isPreset); }
    if (frFields)   { frFields.classList.toggle('wcfr-hidden',   isPreset); }
    if (frFlags)    { frFlags.classList.toggle('wcfr-hidden',    isPreset); }
  }

  /**
   * Build a fresh rule card HTML for a given index.
   *
   * @param {number} index
   * @param {string} strategy  'find_replace' | 'wikimedia_thumbnail_roundup'
   * @returns {HTMLElement}
   */
  function buildRuleCard(index, strategy) {
    strategy = strategy || 'find_replace';
    var isPreset = strategy === 'wikimedia_thumbnail_roundup';

    var wrapper = document.createElement('div');
    wrapper.className = 'wcfr-rule-card';

    var nameValue = isPreset ? 'Wikimedia: Round thumbnail to next allowed size' : '';

    wrapper.innerHTML =
      '<input type="hidden" name="wcfr_rules[' + index + '][id]" value="" />' +
      '<div class="wcfr-rule-header">' +
        '<strong>' + (isPreset ? 'Wikimedia Thumbnail Roundup' : 'New Rule') + '</strong>' +
        '<button type="button" class="button button-small wcfr-remove-rule">Remove</button>' +
      '</div>' +
      '<p><label>Name<br />' +
        '<input type="text" class="regular-text" name="wcfr_rules[' + index + '][name]" value="' + nameValue + '" />' +
      '</label></p>' +
      '<p><label>Strategy<br />' +
        '<select class="wcfr-strategy-select" name="wcfr_rules[' + index + '][strategy]">' +
          '<option value="find_replace"' + (isPreset ? '' : ' selected="selected"') + '>Find/Replace</option>' +
          '<option value="wikimedia_thumbnail_roundup"' + (isPreset ? ' selected="selected"' : '') + '>Wikimedia Thumbnail Roundup</option>' +
        '</select>' +
      '</label></p>' +

      '<div class="wcfr-preset-info' + (isPreset ? '' : ' wcfr-hidden') + '">' +
        '<strong>How this rule works:</strong>' +
        '<p>Scans post content for Wikimedia thumbnail URLs and rewrites the pixel size to the next allowed size. No find/replace text is needed — the URL pattern and size ladder are built in.</p>' +
        '<strong>Matches URLs like:</strong>' +
        '<code>//upload.wikimedia.org/wikipedia/commons/thumb/a/ab/File.png/240px-File.png</code>' +
        '<strong>Rewrites to next size from:</strong>' +
        '<code>20 &rarr; 40 &rarr; 60 &rarr; 120 &rarr; 250 &rarr; 330 &rarr; 500 &rarr; 960 &rarr; 1280 &rarr; 1920 &rarr; 3840 &rarr; (full-size source file)</code>' +
      '</div>' +

      '<div class="wcfr-rule-fields-fr' + (isPreset ? ' wcfr-hidden' : '') + '">' +
        '<p><label>Find<br /><textarea rows="3" class="large-text" name="wcfr_rules[' + index + '][find]"></textarea></label></p>' +
        '<p><label>Replace<br /><textarea rows="3" class="large-text" name="wcfr_rules[' + index + '][replace]"></textarea></label></p>' +
      '</div>' +

      '<p class="wcfr-rule-flags' + (isPreset ? ' wcfr-hidden' : '') + '">' +
        '<label><input type="checkbox" name="wcfr_rules[' + index + '][use_regex]" value="1" /> Use regex</label> ' +
        '<label><input type="checkbox" name="wcfr_rules[' + index + '][ignore_case]" value="1" /> Ignore case</label>' +
      '</p>' +
      '<p>' +
        '<label><input type="checkbox" name="wcfr_rules[' + index + '][enabled]" value="1" checked="checked" /> Enabled</label> ' +
        '<label><input type="checkbox" name="wcfr_rules[' + index + '][apply_in_admin]" value="1" /> Apply in admin</label>' +
      '</p>';

    return wrapper;
  }

  /**
   * Re-index all rule cards so field names stay sequential.
   *
   * @param {HTMLElement} container
   */
  function reindexRules(container) {
    var cards = container.querySelectorAll('.wcfr-rule-card');
    cards.forEach(function (card, i) {
      card.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/wcfr_rules\[\d+\]/, 'wcfr_rules[' + i + ']');
      });
    });
  }

  /**
   * Mirror the preview form's scope inputs into the apply form so a single
   * set of controls governs both actions.
   *
   * @returns {boolean} false to cancel form submit if no rules selected
   */
  window.wcfrMirrorScopeAndConfirm = function (btn) {
    var previewForm = document.getElementById('wcfr-migration-form');
    var mirror      = document.getElementById('wcfr-apply-scope-mirror');
    if (!previewForm || !mirror) {
      return window.confirm('Apply migration now? This writes to post_content.');
    }

    mirror.innerHTML = '';

    var inputs = previewForm.querySelectorAll('input[name^="wcfr_scope"], input[name^="wcfr_scope"]:checked');
    // Collect all checked checkboxes + number inputs from preview form
    previewForm.querySelectorAll('input').forEach(function (input) {
      if (!input.name.startsWith('wcfr_scope')) { return; }
      if ((input.type === 'checkbox' || input.type === 'radio') && !input.checked) { return; }
      var hidden = document.createElement('input');
      hidden.type  = 'hidden';
      hidden.name  = input.name;
      hidden.value = input.value;
      mirror.appendChild(hidden);
    });

    return window.confirm('Apply migration now? This writes to post_content based on the rules and scope shown in the preview form above.');
  };

  document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('wcfr-rules');

    // Wire up existing strategy selects
    if (container) {
      container.querySelectorAll('.wcfr-strategy-select').forEach(applyStrategyToggle);

      // Strategy change
      container.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('wcfr-strategy-select')) {
          applyStrategyToggle(e.target);
        }
      });

      // Remove rule
      container.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('wcfr-remove-rule')) {
          if (window.confirm('Remove this rule?')) {
            e.target.closest('.wcfr-rule-card').remove();
            reindexRules(container);
          }
        }
      });
    }

    // Add blank rule
    var addButton = document.getElementById('wcfr-add-rule');
    if (addButton && container) {
      addButton.addEventListener('click', function () {
        var index = container.querySelectorAll('.wcfr-rule-card').length;
        container.appendChild(buildRuleCard(index, 'find_replace'));
      });
    }

    // Add Wikimedia preset rule
    var addPresetBtn = document.querySelector('.wcfr-add-wikimedia-preset');
    if (addPresetBtn && container) {
      addPresetBtn.addEventListener('click', function () {
        var index = container.querySelectorAll('.wcfr-rule-card').length;
        container.appendChild(buildRuleCard(index, 'wikimedia_thumbnail_roundup'));
        // Hide the button and show note — only one preset needed
        addPresetBtn.style.display = 'none';
      });
    }
  });
})();
