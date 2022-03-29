import {ParticipantCounter, ParticipationState} from "./participant-counter";
import {Labels, TooltipLabel} from "./labels";
import {CombinedMealService} from "./combined-meal-service";

export enum ParticipationAction {
    ACCEPT_OFFER = 'acceptOffer-action',
    DELETE = 'delete-action',
    JOIN = 'join-action',
    SWAP = 'swap-action',
    UNSWAP = 'unswap-action',
}

export interface AcceptOfferData {
    participantID: number
    url: string;
    participantsCount: number;
    bookedDishSlugs: string[];
}

export interface ToggleData extends AcceptOfferData {
    actionText: string;
}

export interface ParticipationUpdateData {
    mealId: number;
    count: number;
    limit: number;
    available: boolean;
    open: boolean;
}

export class ParticipationUpdateHandler {

    public static toggle($checkbox: JQuery, data: ToggleData) {
        // change
        ParticipationUpdateHandler.changeCheckboxState($checkbox);
        const nextAction = ('deleted' === data.actionText) ? ParticipationAction.JOIN : ParticipationAction.DELETE;
        ParticipationUpdateHandler.changeCheckboxAttributes($checkbox, nextAction, data.url);
        ParticipationUpdateHandler.changeParticipationCounter($checkbox, ParticipationState.DEFAULT, data.participantsCount);

        // update
        ParticipationUpdateHandler.updateCheckboxEnabled($checkbox);
        ParticipationUpdateHandler.updateCheckBoxWrapper($checkbox);
        CombinedMealService.updateDish($checkbox, data.participantID, data.bookedDishSlugs);

        let $slotBox = $checkbox.closest('.meal').find('.slot-selector');
        $slotBox.addClass('tmp-disabled').prop('disabled', true);
        $slotBox.parent().children('.loader').css('visibility', 'visible');
    }

    public static acceptOffer($checkbox: JQuery, data: AcceptOfferData): void {
        this.changeToSwapState($checkbox, data.url, data.participantsCount);

        let $dishContainer = $checkbox.closest('.meal-row');
        if (CombinedMealService.isCombinedDish($dishContainer)) {
            CombinedMealService.updateDish($checkbox, data.participantID, data.bookedDishSlugs);
        }
    }

    private static toggleTooltip($checkbox: JQuery, label?: TooltipLabel) {
        let $tooltip = $checkbox.closest('.wrapper-meal-actions').find('.tooltiptext');
        if (undefined !== label) {
            $.getJSON('/labels.json')
                .done(function (labels: Labels) {
                    if ('de' === $('.language-switch').find('span').text()) {
                        $tooltip.text(labels.de.tooltip[label]);
                    } else {
                        $tooltip.text(labels.en.tooltip[label]);
                    }
                    $tooltip.toggleClass('active');
                });
        } else {
            $tooltip.toggleClass('active');
        }
    }

    private static updateCheckBoxWrapper($checkbox: JQuery) {
        let $checkboxWrapper = $checkbox.closest('.checkbox-wrapper');
        $checkboxWrapper.toggleClass('checked', $checkbox.is(':checked'));
        $checkboxWrapper.toggleClass('disabled', $checkbox.is(':disabled'));
    }

    private static updateCheckboxEnabled($checkbox: JQuery, isAvailable: boolean = true, isOpen: boolean = true) {
        let checkboxEnabled =
            isAvailable ||
            (
                !isAvailable &&
                $checkbox.is(':checked') &&
                isOpen
            ) ||
            (
                isOpen &&
                (
                    $checkbox.hasClass(ParticipationAction.SWAP) ||
                    $checkbox.hasClass(ParticipationAction.UNSWAP) ||
                    $checkbox.hasClass(ParticipationAction.ACCEPT_OFFER)
                )
            );

        $checkbox.prop('disabled', !checkboxEnabled);
    }

    private static changeCheckboxState($checkbox: JQuery): void {
        $checkbox
            .prop('checked', !$checkbox.is(':checked'))
            .trigger('change'); // changing checkbox state manually doesn't trigger "change" event
    }

    private static changeCheckboxAttributes($checkbox: JQuery, checkboxClass: ParticipationAction, url: string, participantId?: number) {
        $checkbox.attr('value', url);
        $checkbox.attr('class', 'participation-checkbox ' + checkboxClass);
        if (undefined === participantId) {
            $checkbox.removeData('participant-id');
        } else {
            $checkbox.data('participantId', participantId);
        }
    }

    private static changeParticipationCounter($checkbox: JQuery, state?: ParticipationState, count?: number, limit?: number, available: boolean = true): void {
        let participantCounter = $checkbox.data(ParticipantCounter.NAME);
        if ((undefined !== state) ||
            (undefined !== count && count !== participantCounter.getCount()) ||
            (undefined !== limit && limit !== participantCounter.getLimit())) {
            if (undefined !== count) participantCounter.setNextCount(count + participantCounter.getOffset());
            if (undefined !== limit) participantCounter.setNextLimit(limit);
            if (undefined !== state) participantCounter.setNextParticipationState(state);
            participantCounter.updateUI();
        }

        if (available && $checkbox.is('not:checked')) {
            participantCounter.toggle(available);
        }
    }

    public static updateParticipation($checkbox: JQuery, data: ParticipationUpdateData) {
        // change
        this.changeParticipationCounter($checkbox, undefined, data['count'], data['limit'], data['available']);

        // update
        this.updateCheckboxEnabled($checkbox, data['available'], data['open']);
        this.updateCheckBoxWrapper($checkbox);
    }

    public static changeToOfferIsTaken($checkbox: JQuery) {
        if ($checkbox.hasClass(ParticipationAction.UNSWAP)) {
            // change
            $checkbox.removeClass(ParticipationAction.UNSWAP);
            $checkbox.prop('checked', false).trigger('change');
            $checkbox.attr('value', '');
            this.changeParticipationCounter($checkbox, ParticipationState.DEFAULT);

            // update
            this.updateCheckboxEnabled($checkbox);
            this.updateCheckBoxWrapper($checkbox);
            CombinedMealService.updateDish($checkbox, undefined, []);
            this.toggleTooltip($checkbox);
        }
    }

    public static changeToOfferIsGone($checkbox: JQuery) {
        if ($checkbox.hasClass(ParticipationAction.ACCEPT_OFFER)) {
            // change
            $checkbox.removeClass(ParticipationAction.ACCEPT_OFFER);
            this.changeParticipationCounter($checkbox, ParticipationState.DEFAULT);

            // update
            this.updateCheckboxEnabled($checkbox);
            this.updateCheckBoxWrapper($checkbox);
            this.toggleTooltip($checkbox);
        }
    }

    public static changeToOfferIsAvailable($checkbox: JQuery, url: string) {
        // change
        this.changeCheckboxAttributes($checkbox, ParticipationAction.ACCEPT_OFFER, url);
        let participantCounter = $checkbox.data(ParticipantCounter.NAME);
        if (ParticipationState.OFFER_AVAILABLE !== participantCounter.getParticipationState()) {
            this.changeParticipationCounter($checkbox, ParticipationState.OFFER_AVAILABLE);
        }

        // update
        this.updateCheckboxEnabled($checkbox);
        this.updateCheckBoxWrapper($checkbox);
        this.toggleTooltip($checkbox, TooltipLabel.AVAILABLE_MEAL);
    }

    public static changeToSwapState($checkbox: JQuery, url: string, participantsCount?: number) {
        // change
        $checkbox.prop('checked', true);
        this.changeCheckboxAttributes($checkbox, ParticipationAction.SWAP, url);
        this.changeParticipationCounter($checkbox, ParticipationState.DEFAULT, participantsCount);

        // update
        this.updateCheckboxEnabled($checkbox);
        this.updateCheckBoxWrapper($checkbox);
        this.toggleTooltip($checkbox);
    }

    public static changeToUnswapState($checkbox: JQuery, url: string, participantId: number) {
        // change
        $checkbox.prop('checked', true);
        this.changeCheckboxAttributes($checkbox, ParticipationAction.UNSWAP, url, participantId);
        this.changeParticipationCounter($checkbox, ParticipationState.PENDING);

        // update
        this.updateCheckboxEnabled($checkbox);
        this.updateCheckBoxWrapper($checkbox);
        this.toggleTooltip($checkbox, TooltipLabel.OFFERED_MEAL);
    }
}
