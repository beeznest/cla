<footer id="footer-section" class="sticky-footer bgfooter">
    <div class="container">
        <div class="pre-footer">
            {% if plugin_pre_footer is not null %}
                <div id="plugin_pre_footer">
                    {{ plugin_pre_footer }}
                </div>
            {% endif %}
        </div>
        <div class="sub-footer">
            <div class="row">
                <div class="col-md-4">
                    {% if session_teachers is not null %}
                        <div class="session-teachers">
                            {{ session_teachers }}
                        </div>
                    {% endif %}
                    {% if teachers is not null %}
                        <div class="teachers">
                            {{ teachers }}
                        </div>
                    {% endif %}
                    {% if plugin_footer_left is not null %}
                        <div id="plugin_footer_left">
                            {{ plugin_footer_left }}
                        </div>
                    {% endif %}
                </div>
                <div class="col-md-4">
                    {% if plugin_footer_center is not null %}
                        <div id="plugin_footer_center">
                            {{ plugin_footer_center }}
                        </div>
                    {% endif %}
                </div>
                <div class="col-md-4 text-right">
                    {% if administrator_name is not null %}
                        <div class="administrator-name">
                            {{ administrator_name }}
                        </div>
                    {% endif %}

                    {% if _s.software_name is not empty %}
                        <div class="software-name">
                            <a href="{{ _p.web }}" target="_blank">
                                {{ "PoweredByX" |get_lang|format(_s.software_name) }}
                            </a>&copy; {{ "now"|date("Y") }}
                        </div>
                    {% endif %}

                    {% if plugin_footer_right is not null %}
                        <div id="plugin_footer_right">
                            {{ plugin_footer_right }}
                        </div>
                    {% endif %}
                </div>
            </div>
        </div>
        <div class="extra-footer">
            {{ footer_extra_content }}
        </div>
    </div>
</footer>

<div class="modal fade" id="expand-image-modal" tabindex="-1" role="dialog" aria-labelledby="expand-image-modal-title"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close"|get_lang }}"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="expand-image-modal-title">&nbsp;</h4>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
{# Global modal, load content by AJAX call to href attribute on anchor tag with 'ajax' class #}
<div class="modal fade" id="global-modal" tabindex="-1" role="dialog" aria-labelledby="global-modal-title"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close"|get_lang }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="global-modal-title">&nbsp;</h4>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {

        $("[name\=submitAuth\]").on("click", function () {
            var login;
            var password;
            var inside;
            if($('#formLogin_login').length  &&  $('#formLogin_password').length ){
                login = $("#formLogin_login").val();
                password = $("#formLogin_password").val();
                inside = true;
            } else {
                login = $("#login").val();
                password = $("#password").val();
                inside = false;
            }
            
            $.ajax({
                contentType: "application/x-www-form-urlencoded",
                type: "POST",
                url: "{{ ajax_path }}" + "?a=signIn",
                data: 'login=' + login + '&password=' + password,
                beforeSend: function () {
                    $("#returnMessage").html('<div class="three-quarters-loader"></div>');
                },
                success: function (response) {
                    try {
                        response = JSON.parse(response);
                        $("#returnMessage").html(response['message']);
                        $(location).attr('href', response['url']);    
                    } catch (e) {
                        $("#returnMessage").html(response);
                    }
                }
            });
            if(inside === true){
               location.reload();
            }
            return false;
        });
        
        $("[name\=submitReg\]").on("click", function () {
            var firstname = $("#firstname").val();
            var lastname = $("#lastname").val();
            var username = $("#username").val();
            var pass1 = $("#pass1").val();
            var pass2 = $("#pass2").val();
            $.ajax({
                contentType: "application/x-www-form-urlencoded",
                type: "POST",
                url: "{{ ajax_path }}" + "?a=signUp",
                data: 'firstname=' + firstname + '&lastname=' + lastname + '&username=' + username + '&pass1=' + pass1 + '&pass2=' + pass2,
                beforeSend: function () {
                    $("#returnMessage2").html('<div class="three-quarters-loader"></div>');
                },
                success: function (response) {
                    try {
                        response = JSON.parse(response);
                        $("#returnMessage2").html(response['message']);
                        $.ajax({
                            contentType: "application/x-www-form-urlencoded",
                            type: "POST",
                            url: "{{ ajax_path }}" + "?a=signIn",
                            data: 'login=' + username + '&password=' + pass1,
                            success: function (authLogIn) {
                                if (authLogIn) {
                                    $(location).attr('href', response['url']);
                                }
                            }
                        });
                    } catch (e) {
                        $("#returnMessage2").html(response);
                    }
                }
            });
            return false;
        });
    });
</script>

{% include template ~ '/layout/footer.js.tpl' %}

{{ execution_stats }}