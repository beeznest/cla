
<script>
    $(document).ready(function () {

        $("[name\=submitAuth\]").on("click", function () {
            var login;
            var password;
            var inside;
            if($('#formLogin_login').length  && $('#formLogin_password').length ){
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


<div id="navigation" class="notification-panel">
    {{ help_content }}
    {{ bug_notification }}
</div>
{% block topbar %}
    {% include template ~ "/layout/topbar.tpl" %}
{% endblock %}
<div class="extra-header">{{ header_extra_content }}</div>
<div class="top-header">
    <div class="container">
        <div class="row">
            <div class="col-md-6"></div>
            <div class="col-md-6">

                {% include template ~ "/layout/login_form.tpl" %}
                {% include template ~ "/layout/register_form.tpl" %}
                <script>
                    $(document).on('ready', function () {
                        $("#notifications").load("{{ _p.web_main }}inc/ajax/online.ajax.php?a=get_users_online");
                    });
                </script>
                <div class="section-notifications">
                    <ul id="notifications">
                    </ul>
                    <ul class="option-session">
                        {% if _u.logged  == 0 %}
                            <li>
                                <a href="#" data-toggle="modal" data-target="#login_modal" aria-haspopup="true"
                                   aria-expanded="false">
                                    <i class="fa fa-sign-in fa-lg"></i> {{ "SignIn"|get_lang }}
                                </a>
                            </li>
                            <li>
                                <a href="#" data-toggle="modal" data-target="#register_modal" aria-haspopup="true"
                                   aria-expanded="false">
                                    <i class="fa fa-pencil-square-o fa-lg"></i> {{ 'SignUp'|get_lang }}
                                </a>
                            </li>
                            </li>
                        {% endif %}
                    </ul>
                    {{ accessibility }}
                </div>
            </div>
    </div>
</div>
<header id="header-section" class="header-movil">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="logo">
                    {{ logo }}
                </div>
            </div>
            <div class="col-md-9">

            </div>
        </div>
    </div>
</header>
{% block menu %}
    {% include template ~ "/layout/menu.tpl" %}
{% endblock %}
{% include template ~ "/layout/course_navigation.tpl" %}
