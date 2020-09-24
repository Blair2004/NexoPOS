import * as rx from 'rx';
var EventEmitter = /** @class */ (function () {
    function EventEmitter() {
        this._subject = new rx.Subject;
    }
    EventEmitter.prototype.subject = function () {
        return this._subject;
    };
    EventEmitter.prototype.emit = function (_a) {
        var identifier = _a.identifier, value = _a.value;
        this._subject.onNext({ identifier: identifier, value: value });
    };
    return EventEmitter;
}());
export { EventEmitter };
//# sourceMappingURL=event-emitter.js.map