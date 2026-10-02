export function setPosSetting( settings, key, value ) {
    return {
        ...settings,
        [key]: value,
    };
}

export function unsetPosSetting( settings, key ) {
    const nextSettings = { ...settings };
    delete nextSettings[key];

    return nextSettings;
}
