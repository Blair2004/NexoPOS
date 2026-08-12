/**
 * Require the pointer to cross the target's midpoint along the dominant
 * direction between the dragged and hovered widgets.
 *
 * @param {{ clientX: number, clientY: number }} pointer
 * @param {{ left: number, top: number, width: number, height: number }} draggedRect
 * @param {{ left: number, top: number, width: number, height: number }} hoveredRect
 */
export function hasCrossedWidgetMidpoint( pointer, draggedRect, hoveredRect ) {
    const draggedCenterX = draggedRect.left + draggedRect.width / 2;
    const draggedCenterY = draggedRect.top + draggedRect.height / 2;
    const hoveredCenterX = hoveredRect.left + hoveredRect.width / 2;
    const hoveredCenterY = hoveredRect.top + hoveredRect.height / 2;
    const horizontalDistance = hoveredCenterX - draggedCenterX;
    const verticalDistance = hoveredCenterY - draggedCenterY;

    if ( Math.abs( horizontalDistance ) > Math.abs( verticalDistance ) ) {
        return horizontalDistance > 0
            ? pointer.clientX >= hoveredCenterX
            : pointer.clientX <= hoveredCenterX;
    }

    return verticalDistance > 0
        ? pointer.clientY >= hoveredCenterY
        : pointer.clientY <= hoveredCenterY;
}

/**
 * Prevent geometry changes after a swap from immediately reversing it.
 *
 * @param {{ clientX: number, clientY: number }} pointer
 * @param {{ clientX: number, clientY: number }} lockPoint
 * @param {number} minimumDistance
 */
export function hasMovedBeyondReorderLock( pointer, lockPoint, minimumDistance ) {
    return Math.hypot(
        pointer.clientX - lockPoint.clientX,
        pointer.clientY - lockPoint.clientY
    ) >= minimumDistance;
}
