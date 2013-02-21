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

rules.add = function() {
  var $target = $($(this).data('target'));
  $.get(this.href).done(function(data) {
    $target.find('tbody').append(data);
  });
  return false;
};

rules.remove = function() {
  $(this).closest('tr').remove();
  return false;
};

rules.save = function() {
  $.post(this.action, $(this).serialize()).done(function() {
    alert('Saved!');
  });
  return false;
};

$(function() {
  $('a[data-action="add-rule"]').on('click', rules.add);
  $(document).on('click', 'a[data-action="remove-rule"]', rules.remove);
  $(document).on('submit', 'form[name="rule"]', rules.save);
});
