$(function() {
  $('a[data-delete]').on('click', function() {
    var confirmMsg = $(this).data('delete');
    if (!confirmMsg || confirm(confirmMsg)) {
      var $f = $('<form action="' + this.href + '" method="post"/>').appendTo($(this).parent());
      $f.append('<input type="hidden" name="_METHOD" value="DELETE">');
      $f.submit();
    }
    return false;
  });

  $('.flash.alert-success').each(function() {
    var $this = $(this);
    setTimeout(function() {
      $this.alert('close');
    }, 3000);
  });
});

var rules = {};

rules.define = function() {
  var $target = $(this).closest('.rules').find('.new-rule').show();
  $(this).hide();
  return false;
};

rules.cancel = function() {
  rules.resetNewForm($(this).closest('.new-rule')).hide();
  $(this).closest('.rules').find('a[data-action="rules.define"]').show();
  return false;
}

rules.remove = function() {
  $(this).closest('tr').remove();
  return false;
};

rules.resetNewForm = function($container) {
  $container.
    find('.control-group').removeClass('error').closest('form')[0].reset();
  return $container;
}

rules.save = function() {
  var $trigger = $(this);
  $.post(this.action, $(this).serialize())
    .done(function(data) {
      $trigger.closest('.rules').find('ul').append('<li>' + data + '</li>');
      rules.resetNewForm($trigger.closest('.new-rule'));
    })
    .error(function(data) {
      $trigger.closest('.new-rule').html(data.responseText);
    });
  return false;
};

$(function() {
  $('a[data-action="rules.define"]').on('click', rules.define);
  $(document).on('click','a[data-action="rules.cancel"]', rules.cancel);
  $(document).on('click', 'a[data-action="rules.remove"]', rules.remove);
  $(document).on('submit', 'form[name="rule"]', rules.save);
});
