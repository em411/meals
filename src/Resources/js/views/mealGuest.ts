import {ParticipationGuestToggleHandler} from "../modules/participation-toggle-handler";
import {ParticipationPreToggleHandler} from "../modules/participation-pre-toggle-handler";
import {ParticipationGuestCountUpdateHandler} from "../modules/participation-count-update-handler";

export default class MealGuestView {
    $participationCheckboxes: JQuery;
    $slotDropDown: JQuery;
    mealDate: string;

    constructor() {
        this.$participationCheckboxes = $('.meal-guest input[type="checkbox"]');
        this.$slotDropDown = $('#invitation_form_slot');
        this.mealDate = this.$slotDropDown.closest('.meal-guest').data('date');

        if (this.$slotDropDown.length < 1) {
            this.updateSlots();
            setInterval(this.updateSlots, 3000);
        }

        if (this.$participationCheckboxes.length > 0) {
            let participationToggleHandler = new ParticipationGuestToggleHandler(this.$participationCheckboxes);
            new ParticipationPreToggleHandler(participationToggleHandler);
            new ParticipationGuestCountUpdateHandler(this.$participationCheckboxes);
        }
    }

    private updateSlots() {
        let self = this;

        $.ajax({
            'url': '/participation/slots-status/' + self.mealDate,
            dataType: 'json',
            'success': function (data) {
                $.each(data, function (k, v) {
                    let $slotOption = self.$slotDropDown.find('option[value="'+v.slot+'"]');

                    const slotLimit = $slotOption.data('limit');
                    if (slotLimit > 0) {
                        const slotTitle = $slotOption.data('title');
                        const slotText = slotTitle + ' (' + v.booked+'/'+slotLimit + ')';
                        $slotOption.text(slotText);
                        // disable slot if no. of bookings reached the slot limit
                        if (slotLimit <= v.booked) {
                            $slotOption.prop('disabled', true);
                        }
                    }

                    if (v.booked_by_user) {
                        // do not overwrite user selected value
                        if ('' === self.$slotDropDown.val()) {
                            $slotOption.prop('selected', true);
                        }
                        self.$slotDropDown.prop('disabled', false);
                    }
                });
            }
        });
    }
};