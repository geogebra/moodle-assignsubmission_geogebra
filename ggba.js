/**
 * Created by Christoph on 09.05.14.
 */

ggbparametersin = Y.one('input[name="ggbparameters"]');
ggbviewsin = Y.one('input[name="ggbviews"]');
ggbcodebaseversionin = Y.one('input[name="ggbcodebaseversion"]');

Y.on('submit', function (e) {
    ggbparametersin.set('value', JSON.stringify(applet1.getParameters())); //TODO workaround: ggbOnInit not called if empty file loaded
    parameters.ggbBase64 = ggbApplet.getBase64();
    ggbparametersin.set('value', JSON.stringify(parameters));
    ggbviewsin.set('value', JSON.stringify(applet1.getViews())); //TODO workaround: ggbOnInit not called if empty file loaded
    ggbcodebaseversionin.set('value', applet1.getHTML5CodebaseVersion()); //TODO workaround: ggbOnInit not called if empty file loaded
}, '#mform1');
Y.on('mouseleave', function (e) {
    ggbparametersin.set('value', JSON.stringify(applet1.getParameters())); //TODO workaround: ggbOnInit not called if empty file loaded
    parameters.ggbBase64 = ggbApplet.getBase64();
    if (JSON.stringify(parameters) != ggbparametersin.get('value')) {
        M.core_formchangechecker.set_form_changed();
    }
    ggbparametersin.set('value', JSON.stringify(parameters));
    ggbviewsin.set('value', JSON.stringify(applet1.getViews())); //TODO workaround: ggbOnInit not called if empty file loaded
    ggbcodebaseversionin.set('value', applet1.getHTML5CodebaseVersion()); //TODO workaround: ggbOnInit not called if empty file loaded
}, '#applet_container1');

function ggbOnInit(id) {
    ggbparametersin.set('value', JSON.stringify(applet1.getParameters()));
    ggbviewsin.set('value', JSON.stringify(applet1.getViews()));
    ggbcodebaseversionin.set('value', applet1.getHTML5CodebaseVersion());
}
