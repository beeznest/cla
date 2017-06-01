{% if login_form %}
    <div id="login_modal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{{ 'SignIn' | get_lang }}</h4>
                </div>
                <div class="modal-body">
                    <div id="returnMessage" class="text-center"></div>
                    {{ login_form }}
                    <div class="failed">
                        {{ login_failed }}
                    </div>
                    <div id="extra-form">
                        <div class="row">
                            <div class="col-xs-6">
                                {% if "allow_registration" | api_get_setting != 'false' %}
                                    <a href="{{ _p.web_main }}auth/inscription.php"> {{ 'SignUp' | get_lang }}</a>
                                {% endif %}
                            </div>
                             <div class="col-xs-6">
                                 {% if "allow_lostpassword" | api_get_setting == 'true' %}
                                 <span class="pull-right">
                                     <a href="{{ _p.web_main }}auth/lostPassword.php">{{ 'LostPassword' | get_lang }}</a>
                                 </span>
                                 {% endif %}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endif %}
