var ovpn = ovpn || {};

ovpn.hideFlashes = function() {
  $('.flash.alert-success, .flash.success').each(function() {
    var $this = $(this);
    setTimeout(function() {
      $this.fadeOut('fast', function() { $(this).remove(); });
    }, 3000);
  });
};

ovpn.rules = {};

ovpn.rules.define = function() {
  var $target = $(this).closest('.rules').find('.new-rule').show();
  $(this).hide();
  return false;
};

ovpn.rules.cancel = function() {
  ovpn.rules.resetNewForm($(this).closest('.new-rule')).hide();
  $(this).closest('.rules').find('a[data-action="rules.define"]').show();
  return false;
};

ovpn.rules.remove = function() {
  var $trigger = $(this);
  if (!$trigger.data('confirm') || confirm($trigger.data('confirm'))) {
    $.post(this.href, {'_METHOD': 'DELETE'})
      .done(function() {
        $trigger.closest('li').fadeOut('fast', function() { $(this).remove(); });
      });
  }
  return false;
};

ovpn.rules.resetNewForm = function($container) {
  var $form = $container.find('.control-group').removeClass('error').closest('form');
  $(':input', $form)
    .not(':button, :submit, :reset, :hidden')
    .val('')
    .removeAttr('checked')
    .removeAttr('selected');
  return $container;
};

ovpn.rules.save = function() {
  var $trigger = $(this);
  var createRule = $trigger.find(':submit')[0].name === 'create';
  $.post(this.action, $(this).serialize())
    .done(function(data) {
      if (createRule) {
        $trigger.closest('.rules').find('ul').append('<li>' + data + '</li>');
        ovpn.rules.resetNewForm($trigger.closest('.new-rule'));
      } else {
        $trigger.closest('li').html(data);
      }
    })
    .error(function(data) {
      $trigger.closest(createRule ? '.new-rule' : 'li').html(data.responseText);
    });
  return false;
};

ovpn.rules.sortable = function(selector) {
  $(selector).sortable({
    // containment: 'parent',
    axis: 'y',
    cursor: 'move',
    handle: '.sortable-handle',
    update: function(e, ui) {
      $.post($(this).data('sort-url'), $(this).sortable('serialize', {
        attribute: 'data-id'
      }));
    }
  });
};

ovpn.users = {};

ovpn.users.toggleSuspend = function() {
  var $trigger = $(this);
  $.post(this.href).done(function(data) {
    $trigger.closest('.section').html(data); 
  });
  return false;
};

$(function() {
  $(document).ajaxSuccess(ovpn.hideFlashes);

  $('a[data-delete]').on('click', function() {
    var confirmMsg = $(this).data('delete');
    if (!confirmMsg || confirm(confirmMsg)) {
      var $f = $('<form action="' + this.href + '" method="post"/>').appendTo($(this).parent());
      $f.append('<input type="hidden" name="_METHOD" value="DELETE">');
      $f.submit();
    }
    return false;
  });

  ovpn.hideFlashes();

  $('a[data-action="rules.define"]').on('click', ovpn.rules.define);
  $(document).on('click','a[data-action="rules.cancel"]', ovpn.rules.cancel);
  $(document).on('click', 'a[data-action="rules.remove"]', ovpn.rules.remove);
  $(document).on('submit', 'form[name="rule"]', ovpn.rules.save);
  ovpn.rules.sortable('.rules ul');

  $(document).on('click', 'a[data-action="users.toggleSuspend"]', ovpn.users.toggleSuspend);
});
