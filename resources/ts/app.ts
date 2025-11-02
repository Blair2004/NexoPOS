declare const nsHooks: any;
declare const nsExtraComponents: any;

/**
 * If anything has to happen before mounting
 * that will be the place to do it.
 */
nsHooks.doAction( 'ns-before-mount' );

const dashboardAsideElement = document.querySelector('#dashboard-aside');
if ((window as any).nsDashboardAside && dashboardAsideElement) {
    (window as any).nsDashboardAside.mount(dashboardAsideElement);
}

const dashboardOverlayElement = document.querySelector('#dashboard-overlay');
if ((window as any).nsDashboardOverlay && dashboardOverlayElement) {
    (window as any).nsDashboardOverlay.mount(dashboardOverlayElement);
}

const dashboardHeaderElement = document.querySelector('#dashboard-header');
if ((window as any).nsDashboardHeader && dashboardHeaderElement) {
    (window as any).nsDashboardHeader.mount(dashboardHeaderElement);
}

const dashboardContentElement = document.querySelector('#dashboard-content');
if ((window as any).nsDashboardContent && dashboardContentElement) {
    (window as any).nsDashboardContent.mount(dashboardContentElement);
}