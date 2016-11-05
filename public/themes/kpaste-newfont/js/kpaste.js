function toggleLoginPopup(e)
{
    stopPropagationOnEvent(e);
    $('#popupLogin').slideToggle(300);
}
function showTooltip(element)
{
    $('#userbar-custom-tooltip p').html($(element).attr('title'));
    $(element).attr('title', '');
    var left = $(element).offset().left - $('#userbar-custom-tooltip').outerWidth() / 2;
    var top = $('#userbar-custom-tooltip').outerHeight() + 40;
    $('#userbar-custom-tooltip').css({left: left, top: top} );
    $('#userbar-custom-tooltip img').css({left: $('#userbar-custom-tooltip').outerWidth() / 2 - 8});
    $('#userbar-custom-tooltip').show();
}

function hideTooltip(element) 
{
    var title = $('#userbar-custom-tooltip p').html();
    
    $('#userbar-custom-tooltip').hide();
    $('#userbar-custom-tooltip p').html('');
    $(element).attr({title: title});
}

$(document).ready(function() {
    $('#userbar a').hover(function() { showTooltip(this); }, function() { hideTooltip(this); });
    $('div.formRow input, div.formRow select, div.camptcha input').focus(function() {
        $( this ).parent().children( 'span' ).css({backgroundColor: '#01a4e5'});
    });
    $('div.formRow input, div.formRow select, div.camptcha input').focusout(function() {
        $( this ).parent().children( 'span' ).css({backgroundColor: '#5E5E5E'});
    });
});