Mealz.prototype.toggleParticipation = function ($checkbox) {
    var that = this;
    var $participantsCount = $checkbox.closest('.wrapper-meal-actions').find('.participants-count');
    var url = $checkbox.attr('value');

    $.ajax({
        method: 'GET',
        url: url,
        dataType: 'json',
        success: function (data) {
            if (data.redirect) {
                window.location.replace(data.redirect);
            }
            $checkbox.attr('value', data.url);
            $participantsCount.fadeOut('fast', function () {
                that.applyCheckboxClasses($checkbox);
                $participantsCount.find('span').text(data.participantsCount);
                $participantsCount.fadeIn('fast');
            });
        },
        error: function (xhr) {
            console.log(xhr.status + ': ' + xhr.statusText);
        }
    });
};

Mealz.prototype.toggleParticipationAdmin = function ($element) {
    var url = $element.attr('href');

    $.ajax({
        method: 'GET',
        url: url,
        dataType: 'json',
        success: function (data) {
            $element.attr('href', data.url);
            $element.parent().toggleClass('participating');
            $element.text(data.actionText);
        },
        error: function (xhr) {
            console.log(xhr.status + ': ' + xhr.statusText);
        }
    });
};


Mealz.prototype.toggleGuestParticipation = function ($checkbox) {
    var that = this;
    var $participantsCount = $checkbox.closest('.meal-row').find('.participants-count');
    var actualCount = parseInt($participantsCount.find('span').html());
    $participantsCount.fadeOut('fast', function () {
        that.applyCheckboxClasses($checkbox);
        $participantsCount.find('span').text($checkbox.is(':checked') ? actualCount + 1 : actualCount - 1);
        $participantsCount.fadeIn('fast');
    });
};

Mealz.prototype.selectProfile = function () {
    var profile = $('.profile');
    var profilesJson = $('.profile-list').data('attribute-profiles');
    var profiles = {
        data: profilesJson,
        getValue: 'label',
        list: {
            match: {
                enabled: true
            },
            onSelectItemEvent: function () {
                var value = profile.getSelectedItemData().value;
                var selected = $('.easy-autocomplete-container li')[profile.getSelectedItemIndex()];
                $(selected).attr({'data-attribute-value': value});
            }
        }
    };

    profile.easyAutocomplete(profiles);
};

Mealz.prototype.addProfile = function () {
    var prototype = $('.table-content').data('prototype');
    var selectedProfile = $('.easy-autocomplete-container li[class="selected"]');
    var name = $(selectedProfile)[0].innerText;
    var userName = $(selectedProfile).data('attribute-value');
    prototype = prototype.replace(/__name__/g, name);
    prototype = prototype.replace(/__username__/g, userName);
    // console.log(prototype);
    $(prototype).prependTo("table > tbody");
    Mealz.prototype.initToggleParticipation();
};

Mealz.prototype.showProfiles = function () {
    var profileList = $('.profile-list').data('attribute-profiles');
    var profileListContainer = $('.easy-autocomplete-container ul');

    // show all profiles in list
    $.each(profileList, function( key ) {
        profileListContainer.append('<li data-attribute-value=\"' + profileList[key].value + '\"><div class=\"eac-item\">' + profileList[key].label + '</div></li>');
    });

    // if click on hamburger-menu show profile list
    $('.toggle-profiles').on('click', function (event) {
        profileListContainer.toggle();
        event.preventDefault();
    });

    // if hover li add class selected
    $('.easy-autocomplete-container li').on('click', function () {
        $(this).toggleClass('selected');
    });

    // if click on profil, add profil into input value
    $('.easy-autocomplete-container li div').on('click', function () {
        var selectedItem = $(this)[0].innerHTML;
        $('.profile').val(selectedItem);
    });

    // liste immer neu bauen wenn button gedrückt wird

};
