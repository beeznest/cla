{% if login_form %}
    <div id="register_modal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{{ 'SignUp' | get_lang }}</h4>
                </div>
                <div class="modal-body">
                    <div id="returnMessage2" class="text-center"></div>
                    {{ register_form }}
                </div>
            </div>
        </div>
    </div>
{% endif %}
