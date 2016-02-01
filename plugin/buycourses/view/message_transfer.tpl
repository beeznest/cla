<div>
    <p>{{ 'DearUser'|get_lang }}</p>
    <p>{{ 'PurchaseDetailsIntro'|get_plugin_lang('BuyCoursesPlugin') }}</p>
    <dl>
        <dt>{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.buy_date }}</dd>
        <dt>{{ 'Service'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'StartDate'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.start_date }}</dd>
        <dt>{{ 'Service'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'EndDate'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.end_date }}</dd>
        <dt>{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.reference }}</dd>
        <dt>{{ 'UserName'|get_lang }}</dt>
        <dd>{{ sale.buyer }}</dd>
        <dt>{{ 'Course'|get_lang }}</dt>
        <dd>{{ sale.name }}</dd>
        <dt>{{ 'Service'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.currency ~ ' ' ~ sale.price }}</dd>
    </dl>
    <p>{{ 'BankAccountIntro'|get_plugin_lang('BuyCoursesPlugin')|format(sale.product) }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ 'Name'|get_lang }}</th>
                <th>{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'SWIFT'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            </tr>
        </thead>
        <tbody>
            {% for account in transfer_accounts %}
                <tr>
                    <td>{{ account.name }}</td>
                    <td>{{ account.account }}</td>
                    <td>{{ account.swift }}</td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
    <p>{{ 'PurchaseDetailsEnd'|get_plugin_lang('BuyCoursesPlugin') }}</p>
</div>
