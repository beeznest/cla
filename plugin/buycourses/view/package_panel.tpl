<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

        
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th class="text-center">{{ 'Email'|get_lang }}</th>
            <th>{{ 'User'|get_lang }}</th>
            <th class="text-center">{{ 'Type'|get_lang }}</th>
            <th class="text-center">{{ 'CourseSessionBlock'|get_lang }}</th>
            <th class="text-center">{{ 'Options'|get_lang }}</th>
        </tr>
    </thead>
    <tbody>
        {% for index,subscribers in package %}
            <tr>
                {% if subscribers == false %}
                    <td class="text-center"><input type="email" id="email-{{ index }}" name="email-{{ index }}" placeholder="{{ 'Email' | get_lang }}" required></td>
                    <td>-</td>
                    <td class="text-center">
                        <select id="type-{{ index }}" name="type-{{ index }}" class="form-control">
                            <option value="0">{{ 'None' | get_lang }}</option>
                            <option value="1">{{ 'Course' | get_lang }}</option>
                            <option value="2">{{ 'Session' | get_lang }}</option>
                        </select>
                    </td>
                    <td class="text-center">
                        <select id="typeId-{{ index }}" name="typeId-{{ index }}" class="form-control">
                            <option value="0">{{ 'PleaseSelectAChoice' | get_lang }}</option>
                        </select>
                    </td>
                    <td class="text-center">
                        <a id="link-{{ index }}" href="#" class="btn btn-primary btn-sm">
                            <em id="icon-{{ index }}" class="fa fa-plus-circle fa-fw"></em> {{ 'reg'|get_lang }} {{ 'User'|get_lang }}
                        </a>
                    </td>
                    <script>
                        $(document).ready(function() {
                            $('#type-{{ index }}').on('change', function() {
                                if (this.value == 1) {
                                    {% for course in courses %}
                                        $('#typeId-{{ index }}').html('<option value="{{ course.getId }}">{{ course.getTitle }}</option>');
                                    {% endfor %}
                                } else if (this.value == 2) {
                                    {% for session in sessions %}
                                        $('#typeId-{{ index }}').html('<option value="{{ session.getId }}">{{ session.getName }}</option>');
                                    {% endfor %}
                                }
                            });
                            
                            $("#link-{{ index }}").click(function () {
                                if(confirm("{{ 'ConfirmYourChoice' | get_lang  }}")) {
                                    var email = $("#email-{{ index }}").val();
                                    var type = $("#type-{{ index }}").val();
                                    var typeId = $("#typeId-{{ index }}").val();
                                    var index = {{ index }};
                                    var groupId = {{ group_id }};
                                    $.ajax({
                                        beforeSend: function() {
                                            $("#link-{{ index }}").html('<em class="fa fa-refresh fa-spin"></em> {{ 'Loading' | get_lang }}');
                                        },
                                        type: "POST",
                                        url: "{{ _p.web_plugin ~ 'buycourses/src/buycourses.ajax.php?a=subscribe_user' }}",
                                        data : { subscribe_slot: index, email: email, type: type, typeId: typeId, groupId: groupId },
                                        success: function(response) {
                                            location.reload();
                                        }
                                    });
                                }

                                return false;  
                            });
                            
                            
                        });
                    </script>
                {% else %}
                    <td class="text-center">{{ subscribers.email }}</td>
                    <td>{{ subscribers.complete_name_with_username }}</td>
                    <td class="text-center">{{ subscribers.Type == 1 ? 'Course' | get_lang : 'Session' | get_lang }}</td>
                    <td class="text-center">{{ subscribers.Type == 1 ? subscribers.Course.title : subscribers.Session.name }}</td>
                    <td class="text-center">
                        <a id="link-{{ index }}" href="#" class="btn btn-danger btn-sm">
                            <em class="fa fa-minus-circle fa-fw"></em> {{ 'Unreg'|get_lang }}
                        </a>
                    </td>
                    <script>
                        $(document).ready(function() {
                            
                            $("#link-{{ index }}").click(function () {
                                if(confirm("{{ 'ConfirmYourChoice' | get_lang  }}")) {
                                    
                                    var type = {{ subscribers.Type }};
                                    var typeId = {{ subscribers.Type == 1 ? subscribers.Course.real_id : subscribers.Session.id }};
                                    var index = {{ index }};
                                    var groupId = {{ group_id }};
                                    var userId = {{ subscribers.user_id }};
                                    
                                    $.ajax({
                                        beforeSend: function() {
                                            $("#link-{{ index }}").html('<em class="fa fa-refresh fa-spin"></em> {{ 'Loading' | get_lang }}');
                                        },
                                        type: "POST",
                                        url: "{{ _p.web_plugin ~ 'buycourses/src/buycourses.ajax.php?a=unsubscribe_user' }}",
                                        data : { subscribe_slot: index, type: type, typeId: typeId, userId: userId, groupId: groupId },
                                        success: function(response) {
                                            location.reload();
                                        }
                                    });
                                }

                                return false;  
                            });
                            
                            
                        });
                    </script>
                {% endif %}
            </tr>
        {% endfor %}
    </tbody>
</table>
