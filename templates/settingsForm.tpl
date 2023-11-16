
{block name="page"}
    <h1 class="app__pageHeading">
        {$pageTitle|escape}
    </h1>

    <script type="text/javascript">
        // Attach the JS file tab handler.
        $(function() {
            $('#ompxmlTabs').pkpHandler('$.pkp.controllers.TabHandler');
            $('#ompxmlSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', {
                success: function(data, textStatus, jqXHR) {
                    // Adapte este trecho conforme necessário
                    // Aqui você pode decidir o que fazer após o envio bem-sucedido do formulário
                    alert('Formulário enviado com sucesso!');

                    // Por exemplo, redirecionar para a página desejada
                    window.location.href = '{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings"}';
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Adapte este trecho conforme necessário
                    // Aqui você pode lidar com erros de submissão
                    alert('Erro ao enviar o formulário: ' + errorThrown);
                }
            });
        });
    </script>

    <div id="ompxmlTabs" class="pkp_controllers_tab">
        <ul>
            <li><a href="#credentialsTab">{translate key="Credentials"}</a></li>
            <li><a href="#specialTab">{translate key="Special"}</a></li>
        </ul>

        <div id="credentialsTab">
            <div class="pkp_notification">
                <div class="notifyWarning">
                    <h3>{translate key="Adicione as credenciais da Crossref"}</h3>
                </div>
            </div>

            <form class="pkp_form" id="ompxmlSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
                {csrf}
                {include file="controllers/notification/inPlaceNotification.tpl" notificationId="ompxmlSettingsFormNotification"}
                
                <div id="description">{translate key="plugins.generic.ompxml.manager.settings.description"}</div>

                {fbvFormArea id="ompxmlSettingsFormArea"}
                    {fbvFormSection}
                        {fbvElement type="text" id="email" value=$email label="Email" maxlength="100" size=$fbvStyles.size.MEDIUM}
                        {fbvElement type="text" id="username" value=$username label="Nome de Usuário" maxlength="50" size=$fbvStyles.size.MEDIUM}
                    {/fbvFormSection}
                    {fbvFormButtons}
                {/fbvFormArea}

                <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
            </form>
        </div>

        <div id="specialTab">
            <p>{translate key="calma calabreso"}</p>
        </div>
    </div>
{/block}
