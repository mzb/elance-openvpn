var ovpn = ovpn || {};

ovpn.getCSRFKeyAndToken = function() {
  var key = 'csrf_token';
  var token = $('meta[name="' + key + '"]').attr('content');
  return {
    key: key,
    token: token
  };
};

ovpn.addCSRFTokenToForm = function($form) {
  var csrf = ovpn.getCSRFKeyAndToken();
  $('<input type="hidden" name="' + csrf.key + '" value="' + csrf.token + '" />')
    .appendTo($form);
};

ovpn.addCSRFTokenToRequest = function(request) {
  if (!request.type.toUpperCase().match(/POST|PUT|DELETE/)) {
    return;
  }

  var csrf = ovpn.getCSRFKeyAndToken();
  var csrfParam = csrf.key + '=' + csrf.token;
  var csrfParamPresent = new RegExp(csrfParam).test(request.data);
  if (!csrfParamPresent) {
    request.data = (request.data ? request.data + '&' : '') + csrfParam;
  }
};

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
        $trigger.closest('li').fadeOut('fast', function() { 
          if ($trigger.closest('ul').find('li').length == 1) {
            $trigger.closest('.rules').find('a[data-action="rules.saveAll"]').hide();
          }
          $(this).remove();
        });
      });
  }
  return false;
};

ovpn.rules.resetNewForm = function($container) {
  var $form = $container.find('.control-group').removeClass('error').closest('form');

  $(':input', $form).not(':button, :submit, :reset, :hidden, :checkbox, select')
    .val('');
  $(':checkbox', $form).removeAttr('checked');
  $('select', $form).removeAttr('selected');
  
  $container.find('.flash').remove();
  
  return $container;
};

ovpn.rules.save = function() {
  var $trigger = $(this);
  var createRule = $trigger.find(':submit')[0].name === 'create';
  $.post(this.action, $(this).serialize())
    .done(function(data) {
      if (createRule) {
        $trigger.closest('.rules').find('ul').append('<li>' + data + '</li>');
        var id = $trigger.closest('.rules').find('li:last form').data('id');
        $trigger.closest('.rules').find('li:last').attr('data-id', 'rule-' + id);
        $trigger.closest('.rules').find('a[data-action="rules.saveAll"]').show();
        ovpn.rules.resetNewForm($trigger.closest('.new-rule'));
      } else {
        $trigger.closest('li').html(data);
      }
    })
    .error(function(data) {
      if (createRule) {
        $trigger.closest('.new-rule').find('.section').html(data.responseText);
      } else {
        $trigger.closest('li').html(data.responseText);
      }
    });
  return false;
};

ovpn.rules.saveAll = function() {
  $(this).closest('.rules').find('ul form')
    .trigger('submit');
  return false;
};

ovpn.rules.sortable = function(selector) {
  $(selector).sortable({
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
  ovpn.addCSRFTokenToForm($('form'));
  $.ajaxPrefilter(ovpn.addCSRFTokenToRequest);

  $(document).ajaxSuccess(ovpn.hideFlashes);

  $('a[data-delete]').on('click', function() {
    var confirmMsg = $(this).data('delete');
    if (!confirmMsg || confirm(confirmMsg)) {
      var $f = $('<form action="' + this.href + '" method="post"/>').appendTo($(this).parent());
      $f.append('<input type="hidden" name="_METHOD" value="DELETE">');
      ovpn.addCSRFTokenToForm($f);
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
  $('a[data-action="rules.saveAll"]').on('click', ovpn.rules.saveAll);

  $(document).on('click', 'a[data-action="users.toggleSuspend"]', ovpn.users.toggleSuspend);
});
