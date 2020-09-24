import moment from "moment";
/**
 * till will make sure the frontend
 * time remain in sync or almost with
 * the backend date
 */
ns.date.moment = moment(ns.date.current);
/**
 * define the interval that will
 * increate the value of the date
 * locally
 * @param {Interval} interval
 */
ns.date.interval = setInterval(function () {
    ns.date.moment.add(1, 'seconds');
}, 1000);
//# sourceMappingURL=time.js.map