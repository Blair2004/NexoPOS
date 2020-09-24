var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import { BehaviorSubject } from "rxjs";
var State = /** @class */ (function () {
    function State(state) {
        var _this = this;
        this.state = {};
        this.behaviorState = new BehaviorSubject({});
        this.behaviorState.subscribe(function (state) {
            _this.state = state;
        });
        this.setState(state);
    }
    State.prototype.setState = function (object) {
        this.behaviorState.next(__assign(__assign({}, this.state), { object: object }));
    };
    return State;
}());
export { State };
//# sourceMappingURL=state.js.map