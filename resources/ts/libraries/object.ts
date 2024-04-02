export function insertBeforeKey(obj, key, newKey, value) {
    const newObj = {};
    Object.keys(obj).forEach((k) => {
      if (k === key) {
        newObj[newKey] = value;
      }
      newObj[k] = obj[k];
    });
    return newObj;
}

export function insertAfterKey(obj, key, newKey, value) {
    const newObj = {};
    Object.keys(obj).forEach((k) => {
        newObj[k] = obj[k];
        if (k === key) {
        newObj[newKey] = value;
        }
    });
    if (!newObj[newKey]) {
        newObj[newKey] = value;
    }
    return newObj;
}