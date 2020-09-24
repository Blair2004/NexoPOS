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
import * as rxjs from 'rxjs';
var HttpClient = /** @class */ (function () {
    function HttpClient() {
        this._subject = new rxjs.Subject;
    }
    HttpClient.prototype.defineClient = function (client) {
        this._client = client;
    };
    HttpClient.prototype.post = function (url, data, config) {
        if (config === void 0) { config = {}; }
        return this._request('post', url, data, config);
    };
    HttpClient.prototype.get = function (url, config) {
        if (config === void 0) { config = {}; }
        return this._request('get', url, undefined, config);
    };
    HttpClient.prototype.delete = function (url, config) {
        if (config === void 0) { config = {}; }
        return this._request('delete', url, undefined, config);
    };
    HttpClient.prototype.put = function (url, data, config) {
        if (config === void 0) { config = {}; }
        return this._request('put', url, data, config);
    };
    HttpClient.prototype._request = function (type, url, data, config) {
        var _this = this;
        if (data === void 0) { data = {}; }
        if (config === void 0) { config = {}; }
        this._subject.next({ identifier: 'async.start', url: url, data: data });
        return new rxjs.Observable(function (observer) {
            _this._client[type](url, data, __assign(__assign({}, _this._client.defaults[type]), { config: config })).then(function (result) {
                observer.next(result.data);
                observer.complete();
                _this._subject.next({ identifier: 'async.stop' });
            }).catch(function (error) {
                observer.error(error.response.data);
                _this._subject.next({ identifier: 'async.stop' });
            });
        });
    };
    HttpClient.prototype.subject = function () {
        return this._subject;
    };
    HttpClient.prototype.emit = function (_a) {
        var identifier = _a.identifier, value = _a.value;
        this._subject.next({ identifier: identifier, value: value });
    };
    return HttpClient;
}());
export { HttpClient };
//# sourceMappingURL=http-client.js.map