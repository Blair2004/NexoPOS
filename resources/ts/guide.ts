import { driver } from "driver.js";
import { nsHttpClient, nsNotice } from "./bootstrap";
import { __ } from "./libraries/lang";

type GuideAction = {
    type: 'click';
    element?: string;
};

type GuideStep = {
    id: string;
    element?: string;
    route?: string;
    popover?: {
        title?: string;
        description?: string;
        side?: 'top' | 'right' | 'bottom' | 'left';
        align?: 'start' | 'center' | 'end';
    };
    nextAction?: GuideAction;
};
           
class GuideService {
    constructor(){
        this.loadGuides();
    }

    async executeGuideAction(
        action: GuideAction,
        highlightedElement?: Element
    ) {
        switch (action.type) {
            case 'click': {
                const target = action.element
                    ? document.querySelector(action.element)
                    : highlightedElement;

                if (target instanceof HTMLElement) {
                    target.click();
                }

                break;
            }
        }
    }

    loadGuides() {
        nsHttpClient.post('/api/user/guides', {
            route: ns.current_route,
            path: window.location.pathname
        }).subscribe({
            next: guides => {
                if ( guides.length > 0 ) {
                    const first = guides[0];

                    const guide = driver({
                        steps: first.steps,
                        showProgress: true,
                        showButtons: [ 'next', 'previous' ],
                        popoverClass: 'ns-driver-popover',
                        onDoneClick: ( element, step, options ) => {
                            options.driver.destroy();
                            this.completeGuide(first.id);
                        },
                        onNextClick: async (element, step, options) => {
                            const guideStep = step as GuideStep;

                            if (guideStep.nextAction) {
                                await this.executeGuideAction(
                                    guideStep.nextAction,
                                    element
                                );
                            }

                            options.driver.moveNext();
                        }
                    })

                    nsNotice.default(
                        first.title,
                        first.description, {
                            actions: {
                                'start': {
                                    label: __( 'Start Guide' ),
                                    className: 'primary',
                                    onClick: ( instance ) => {
                                        guide.drive();
                                        instance.close();
                                    }
                                },
                                'dismiss': {
                                    label: __( 'Dismiss' ),
                                    className: 'btn btn-secondary',
                                    onClick: ( instance ) => {
                                        instance.close();
                                        this.dismissGuide(first.id);
                                    }
                                }
                            }
                        }
                    )
                }
            }
        })
    }

    dismissGuide( identifier : string) {
        nsHttpClient.post('/api/user/guides/dismiss', { identifier }).subscribe({
            next: () => {
                console.log('Guide dismissed');
            }
        })
    }

    completeGuide( identifier : string) {
        nsHttpClient.post('/api/user/guides/complete', { identifier }).subscribe({
            next: () => {
                console.log('Guide completed');
            }
        })
    }
}

new GuideService;