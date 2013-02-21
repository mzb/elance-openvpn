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
