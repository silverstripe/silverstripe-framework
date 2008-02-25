<?php

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('pt_PT', $lang) && is_array($lang['pt_PT'])) {
	$lang['pt_PT'] = array_merge($lang['en_US'], $lang['pt_PT']);
} else {
	$lang['pt_PT'] = $lang['en_US'];
}

$lang['pt_PT']['BasicAuth']['ENTERINFO'] = 'Por favor insira um nome de utilizador e password.';
$lang['pt_PT']['BasicAuth']['ERRORNOTADMIN'] = 'Esse utilizador não é um administrador.';
$lang['pt_PT']['BasicAuth']['ERRORNOTREC'] = 'Esse nome de utilizador / password não é válido';
$lang['pt_PT']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = 'Modificou a sua password para';
$lang['pt_PT']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT2'] = 'Pode utilizar agora as seguintes credenciais para se autenticar:';
$lang['pt_PT']['ChangePasswordEmail.ss']['HELLO'] = 'Olá';
$lang['pt_PT']['ComplexTableField.ss']['ADDITEM'] = 'Adicionar';
$lang['pt_PT']['ComplexTableField.ss']['DELETE'] = 'apagar';
$lang['pt_PT']['ComplexTableField.ss']['DELETEROW'] = 'Apagar esta linha';
$lang['pt_PT']['ComplexTableField.ss']['EDIT'] = 'editar';
$lang['pt_PT']['ComplexTableField.ss']['NOITEMSFOUND'] = 'Nenhum item encontrado';
$lang['pt_PT']['ComplexTableField.ss']['SHOW'] = 'mostrar';
$lang['pt_PT']['ComplexTableField.ss']['SORTASC'] = 'Ordenar ascendente';
$lang['pt_PT']['ComplexTableField.ss']['SORTDESC'] = 'Ordenar descendente';
$lang['pt_PT']['ComplexTableField_popup.ss']['NEXT'] = 'Próximo';
$lang['pt_PT']['ComplexTableField_popup.ss']['PREVIOUS'] = 'Anterior';
$lang['pt_PT']['ContentController']['DRAFT_SITE_ACCESS_RESTRICTION'] = 'Tem de se autenticar para ver o conteúdo de rascunho ou arquivado. <a href="%s">Clique aqui para voltar ao site publicado</a>';
$lang['pt_PT']['Controller']['FILE'] = 'Ficheiro';
$lang['pt_PT']['Controller']['IMAGE'] = 'Imagem';
$lang['pt_PT']['Date']['AGO'] = 'atrás';
$lang['pt_PT']['Date']['AWAY'] = 'ausente';
$lang['pt_PT']['Date']['DAY'] = 'dia';
$lang['pt_PT']['Date']['DAYS'] = 'dias';
$lang['pt_PT']['Date']['HOUR'] = 'hora';
$lang['pt_PT']['Date']['HOURS'] = 'horas';
$lang['pt_PT']['Date']['MIN'] = 'min';
$lang['pt_PT']['Date']['MINS'] = 'mins';
$lang['pt_PT']['Date']['MONTH'] = 'mês';
$lang['pt_PT']['Date']['MONTHS'] = 'meses';
$lang['pt_PT']['Date']['SEC'] = 'seg';
$lang['pt_PT']['Date']['SECS'] = 'segs';
$lang['pt_PT']['Date']['YEAR'] = 'ano';
$lang['pt_PT']['Date']['YEARS'] = 'anos';
$lang['pt_PT']['DateField']['VALIDDATEFORMAT'] = 'Por favor insira uma data no formato válido (DD/MM/AAAA).';
$lang['pt_PT']['DropdownField']['CHOOSE'] = '(Escolha)';
$lang['pt_PT']['EmailField']['VALIDATION'] = 'Por favor insira um endereço de email.';
$lang['pt_PT']['ErrorPage']['CODE'] = 'Código de erro';
$lang['pt_PT']['FileIframeField']['NOTEADDFILES'] = 'Pode adicionar ficheiros assim que seja gravado pela primeira vez.';
$lang['pt_PT']['ForgotPasswordEmail.ss']['HELLO'] = 'Olá';
$lang['pt_PT']['Form']['DATENOTSET'] = '(Nenhuma data inserida)';
$lang['pt_PT']['Form']['FIELDISREQUIRED'] = '%s é de preenchimento obrigatório';
$lang['pt_PT']['Form']['LANGAOTHER'] = 'Outras línguas';
$lang['pt_PT']['Form']['LANGAVAIL'] = 'Línguas disponíveis';
$lang['pt_PT']['Form']['NOTSET'] = '(não definido)';
$lang['pt_PT']['Form']['SAVECHANGES'] = 'Gravar alterações';
$lang['pt_PT']['Form']['VALIDATIONALLDATEVALUES'] = 'Por favor certifique-se que inseriu todas as datas';
$lang['pt_PT']['Form']['VALIDATIONBANKACC'] = 'Por favor insira um número de banco válido';
$lang['pt_PT']['Form']['VALIDATIONCREDITNUMBER'] = 'Por favor certifique-se que inseriu o número do cartão de crédito %s correctamente.';
$lang['pt_PT']['Form']['VALIDATIONFAILED'] = 'Campo de validação';
$lang['pt_PT']['Form']['VALIDATIONNOTUNIQUE'] = 'O valor inserido não é único';
$lang['pt_PT']['Form']['VALIDATIONPASSWORDSDONTMATCH'] = 'Passwords não coincidem';
$lang['pt_PT']['Form']['VALIDATIONPASSWORDSNOTEMPTY'] = 'Passwords não podem estar em branco';
$lang['pt_PT']['Form']['VALIDATIONSTRONGPASSWORD'] = 'Passwords devem ter ao menos 1 dígito e 1 alfanumérico.';
$lang['pt_PT']['Form']['VALIDCURRENCY'] = 'Por favor insira uma moeda válida.';
$lang['pt_PT']['GhostPage']['NOLINKED'] = 'Esta página fantasma não tem nenhum link.';
$lang['pt_PT']['GSTNumberField']['VALIDATION'] = 'Por favor insira um número GST válido';
$lang['pt_PT']['HtmlEditorField']['ALTTEXT'] = 'Descrição';
$lang['pt_PT']['HtmlEditorField']['ANCHOR'] = 'Inserir/editar âncora';
$lang['pt_PT']['HtmlEditorField']['BULLETLIST'] = 'Lista sem ordenação';
$lang['pt_PT']['HtmlEditorField']['BUTTONALIGNCENTER'] = 'Alinhar ao Centro';
$lang['pt_PT']['HtmlEditorField']['BUTTONALIGNJUSTIFY'] = 'Justificado';
$lang['pt_PT']['HtmlEditorField']['BUTTONALIGNLEFT'] = 'Alinhar à Esquerda';
$lang['pt_PT']['HtmlEditorField']['BUTTONALIGNRIGHT'] = 'Alinhar à Direita';
$lang['pt_PT']['HtmlEditorField']['BUTTONBOLD'] = 'Negrito (Ctrl+B)';
$lang['pt_PT']['HtmlEditorField']['BUTTONCANCEL'] = 'Cancelar';
$lang['pt_PT']['HtmlEditorField']['BUTTONEDITIMAGE'] = 'Editar imagem';
$lang['pt_PT']['HtmlEditorField']['BUTTONINSERTFLASH'] = 'Inserir Flash';
$lang['pt_PT']['HtmlEditorField']['BUTTONINSERTIMAGE'] = 'Inserir imagem';
$lang['pt_PT']['HtmlEditorField']['BUTTONINSERTLINK'] = 'Inserir link';
$lang['pt_PT']['HtmlEditorField']['BUTTONITALIC'] = 'Itálico (Ctrl+I)';
$lang['pt_PT']['HtmlEditorField']['BUTTONREMOVELINK'] = 'Remover link';
$lang['pt_PT']['HtmlEditorField']['BUTTONSTRIKE'] = 'Rasurado';
$lang['pt_PT']['HtmlEditorField']['BUTTONUNDERLINE'] = 'Sublinhado (Ctrl+U)';
$lang['pt_PT']['HtmlEditorField']['CHARMAP'] = 'Inserir Símbolo';
$lang['pt_PT']['HtmlEditorField']['COPY'] = 'Copiar (Ctrl+C)';
$lang['pt_PT']['HtmlEditorField']['CREATEFOLDER'] = 'criar pasta';
$lang['pt_PT']['HtmlEditorField']['CSSCLASS'] = 'Alinhamento / estilo';
$lang['pt_PT']['HtmlEditorField']['CSSCLASSCENTER'] = 'Centrado sozinho.';
$lang['pt_PT']['HtmlEditorField']['CSSCLASSLEFT'] = 'Na esquerda, com texto envolvido.';
$lang['pt_PT']['HtmlEditorField']['CSSCLASSRIGHT'] = 'Na direita, com texto envolvido.';
$lang['pt_PT']['HtmlEditorField']['CUT'] = 'Cortar (Ctrl+X)';
$lang['pt_PT']['HtmlEditorField']['DELETECOL'] = 'Apagar coluna';
$lang['pt_PT']['HtmlEditorField']['DELETEROW'] = 'Apagar linha';
$lang['pt_PT']['HtmlEditorField']['EDITCODE'] = 'Editar Código HTML';
$lang['pt_PT']['HtmlEditorField']['EMAIL'] = 'Endereço email';
$lang['pt_PT']['HtmlEditorField']['FILE'] = 'Ficheiro';
$lang['pt_PT']['HtmlEditorField']['FLASH'] = 'Inserir flash';
$lang['pt_PT']['HtmlEditorField']['FOLDER'] = 'Pasta';
$lang['pt_PT']['HtmlEditorField']['FOLDERCANCEL'] = 'cancelar';
$lang['pt_PT']['HtmlEditorField']['FORMATADDR'] = 'Morada';
$lang['pt_PT']['HtmlEditorField']['FORMATH1'] = 'Cabeçalho 1';
$lang['pt_PT']['HtmlEditorField']['FORMATH2'] = 'Cabeçalho 2';
$lang['pt_PT']['HtmlEditorField']['FORMATH3'] = 'Cabeçalho 3';
$lang['pt_PT']['HtmlEditorField']['FORMATH4'] = 'Cabeçalho 4';
$lang['pt_PT']['HtmlEditorField']['FORMATH5'] = 'Cabeçalho 5';
$lang['pt_PT']['HtmlEditorField']['FORMATH6'] = 'Cabeçalho 6';
$lang['pt_PT']['HtmlEditorField']['FORMATP'] = 'Parágrafo';
$lang['pt_PT']['HtmlEditorField']['HR'] = 'Inserir Linha Horizontal';
$lang['pt_PT']['HtmlEditorField']['IMAGE'] = 'Inserir imagem';
$lang['pt_PT']['HtmlEditorField']['IMAGEDIMENSIONS'] = 'Dimensões';
$lang['pt_PT']['HtmlEditorField']['IMAGEHEIGHTPX'] = 'Altura';
$lang['pt_PT']['HtmlEditorField']['IMAGEWIDTHPX'] = 'Largura';
$lang['pt_PT']['HtmlEditorField']['INDENT'] = 'Aumentar tabulação';
$lang['pt_PT']['HtmlEditorField']['INSERTCOLAFTER'] = 'Inserir coluna após';
$lang['pt_PT']['HtmlEditorField']['INSERTCOLBEF'] = 'Inserir coluna antes';
$lang['pt_PT']['HtmlEditorField']['INSERTROWAFTER'] = 'Inserir linha depois';
$lang['pt_PT']['HtmlEditorField']['INSERTROWBEF'] = 'Inserir linha antes';
$lang['pt_PT']['HtmlEditorField']['INSERTTABLE'] = 'Inserir tabela';
$lang['pt_PT']['HtmlEditorField']['LINK'] = 'Inserir/editar link no texto seleccionado';
$lang['pt_PT']['HtmlEditorField']['LINKDESCR'] = 'Descrição do link';
$lang['pt_PT']['HtmlEditorField']['LINKEMAIL'] = 'Endereço email';
$lang['pt_PT']['HtmlEditorField']['LINKEXTERNAL'] = 'Outro site';
$lang['pt_PT']['HtmlEditorField']['LINKFILE'] = 'Descarregar ficheiro';
$lang['pt_PT']['HtmlEditorField']['LINKINTERNAL'] = 'Página no site';
$lang['pt_PT']['HtmlEditorField']['LINKOPENNEWWIN'] = 'Abrir link noutra janela?';
$lang['pt_PT']['HtmlEditorField']['LINKTO'] = 'Link para';
$lang['pt_PT']['HtmlEditorField']['OK'] = 'ok';
$lang['pt_PT']['HtmlEditorField']['OL'] = 'Lista ordenada';
$lang['pt_PT']['HtmlEditorField']['OUTDENT'] = 'Diminuir tabulação';
$lang['pt_PT']['HtmlEditorField']['PAGE'] = 'Página';
$lang['pt_PT']['HtmlEditorField']['PASTE'] = 'Colar (Ctrl+V)';
$lang['pt_PT']['HtmlEditorField']['REDO'] = 'Refazer (Ctrl+Y)';
$lang['pt_PT']['HtmlEditorField']['UNDO'] = 'Voltar (Ctrl+Z)';
$lang['pt_PT']['HtmlEditorField']['UNLINK'] = 'Remover link';
$lang['pt_PT']['HtmlEditorField']['UPLOAD'] = 'enviar';
$lang['pt_PT']['HtmlEditorField']['URL'] = 'URL';
$lang['pt_PT']['HtmlEditorField']['VISUALAID'] = 'Mostrar/esconder guias';
$lang['pt_PT']['ImageField']['NOTEADDIMAGES'] = 'Pode adicionar imagens assim que seja gravado pela primeira vez.';
$lang['pt_PT']['ImageUplaoder']['ONEFROMFILESTORE'] = 'Com uma da área de ficheiros';
$lang['pt_PT']['ImageUploader']['ATTACH'] = 'Anexar %s';
$lang['pt_PT']['ImageUploader']['DELETE'] = 'Apagar %s';
$lang['pt_PT']['ImageUploader']['FROMCOMPUTER'] = 'Do seu computador';
$lang['pt_PT']['ImageUploader']['FROMFILESTORE'] = 'Da área de ficheiros do Site';
$lang['pt_PT']['ImageUploader']['ONEFROMCOMPUTER'] = 'Com uma do seu computador';
$lang['pt_PT']['ImageUploader']['REALLYDELETE'] = 'Deseja mesmo apagar %s?';
$lang['pt_PT']['ImageUploader']['REPLACE'] = 'Substituir %s';
$lang['pt_PT']['Image_iframe.ss']['TITLE'] = 'Iframe de envio de Imagem';
$lang['pt_PT']['Member']['ADDRESS'] = 'Morada';
$lang['pt_PT']['Member']['BUTTONCHANGEPASSWORD'] = 'Alterar Password';
$lang['pt_PT']['Member']['BUTTONLOGIN'] = 'Entrar';
$lang['pt_PT']['Member']['BUTTONLOGINOTHER'] = 'Autenticar-se com outras credenciais';
$lang['pt_PT']['Member']['BUTTONLOSTPASSWORD'] = 'Recuperar Password';
$lang['pt_PT']['Member']['CONFIRMNEWPASSWORD'] = 'Confirmar Nova Password';
$lang['pt_PT']['Member']['CONFIRMPASSWORD'] = 'Confirmar Password';
$lang['pt_PT']['Member']['CONTACTINFO'] = 'Informações de Contacto';
$lang['pt_PT']['Member']['EMAIL'] = 'Email';
$lang['pt_PT']['Member']['EMAILPASSWORDAPPENDIX'] = 'A sua password foi alterada. Por favor, guarde este email para referência futura.';
$lang['pt_PT']['Member']['EMAILPASSWORDINTRO'] = 'Aqui está a sua nova password';
$lang['pt_PT']['Member']['EMAILSIGNUPINTRO1'] = 'Obrigado por se registar e se tornar novo membro, os seus detalhes estão abaixo para referência futura.';
$lang['pt_PT']['Member']['EMAILSIGNUPINTRO2'] = 'Pode efectuar a autenticação no website com as credenciais abaixo';
$lang['pt_PT']['Member']['EMAILSIGNUPSUBJECT'] = 'Obrigado por se registar';
$lang['pt_PT']['Member']['ERRORNEWPASSWORD'] = 'As passwords novas não coincidem, por favor tente novamente';
$lang['pt_PT']['Member']['ERRORPASSWORDNOTMATCH'] = 'A sua password actual está errada, por favor tente novamente';
$lang['pt_PT']['Member']['ERRORWRONGCRED'] = 'Não aparenta ser o seu email correcto ou password. Por favor tente novamente.';
$lang['pt_PT']['Member']['FIRSTNAME'] = 'Primeiro nome';
$lang['pt_PT']['Member']['GREETING'] = 'Bem vindo';
$lang['pt_PT']['Member']['INTERFACELANG'] = 'Linguagem do Interface';
$lang['pt_PT']['Member']['LOGGEDINAS'] = 'Está autenticado como %s.';
$lang['pt_PT']['Member']['MOBILE'] = 'Telemóvel';
$lang['pt_PT']['Member']['NAME'] = 'Nome';
$lang['pt_PT']['Member']['NEWPASSWORD'] = 'Nova Password';
$lang['pt_PT']['Member']['PASSWORD'] = 'Password';
$lang['pt_PT']['Member']['PASSWORDCHANGED'] = 'A sua password foi alterada e uma copia foi enviada para si por email.';
$lang['pt_PT']['Member']['PERSONALDETAILS'] = 'Detalhes Pessoais';
$lang['pt_PT']['Member']['PHONE'] = 'Telefone';
$lang['pt_PT']['Member']['REMEMBERME'] = 'Lembrar-se de mim?';
$lang['pt_PT']['Member']['SUBJECTPASSWORDCHANGED'] = 'A sua password foi alterada';
$lang['pt_PT']['Member']['SUBJECTPASSWORDRESET'] = 'Link para recuperar a password';
$lang['pt_PT']['Member']['SURNAME'] = 'Sobrenome';
$lang['pt_PT']['Member']['USERDETAILS'] = 'Detalhes do Utilizador';
$lang['pt_PT']['Member']['VALIDATIONMEMBEREXISTS'] = 'Já existe um membro com este email';
$lang['pt_PT']['Member']['WELCOMEBACK'] = 'Bem vindo de volta, %s';
$lang['pt_PT']['Member']['YOUROLDPASSWORD'] = 'Password antiga';
$lang['pt_PT']['MemberAuthenticator']['TITLE'] = 'Email e Password';
$lang['pt_PT']['NumericField']['VALIDATION'] = '\'%s\' não é um número, apenas números podem ser inseridos neste campo';
$lang['pt_PT']['PhoneNumberField']['VALIDATION'] = 'Por favor insira um número de telefone válido';
$lang['pt_PT']['RedirectorPage']['HASBEENSETUP'] = 'Uma página de redireccionamento foi criada sem nenhum destino.';
$lang['pt_PT']['RedirectorPage']['HEADER'] = 'Esta página irá redireccionar os utilizadores para outra página';
$lang['pt_PT']['RedirectorPage']['OTHERURL'] = 'Outro Site';
$lang['pt_PT']['RedirectorPage']['REDIRECTTO'] = 'Redireccionar para';
$lang['pt_PT']['RedirectorPage']['REDIRECTTOEXTERNAL'] = 'Outro site';
$lang['pt_PT']['RedirectorPage']['REDIRECTTOPAGE'] = 'Uma página no seu site';
$lang['pt_PT']['RedirectorPage']['YOURPAGE'] = 'Página no seu Site';
$lang['pt_PT']['Security']['ALREADYLOGGEDIN'] = 'Não tem acesso a esta página. Se tem outras credenciais que lhe permitem aceder a esta página, pode-se autenticar abaixo.';
$lang['pt_PT']['Security']['BUTTONSEND'] = 'Enviar o link para recuperar a password';
$lang['pt_PT']['Security']['CHANGEPASSWORDBELOW'] = 'Pode modificar a sua password abaixo.';
$lang['pt_PT']['Security']['CHANGEPASSWORDHEADER'] = 'Mudar a password';
$lang['pt_PT']['Security']['ENTERNEWPASSWORD'] = 'Por favor insira uma nova password.';
$lang['pt_PT']['Security']['ERRORPASSWORDPERMISSION'] = 'Tem de estar autenticado para poder alterar a sua password!';
$lang['pt_PT']['Security']['LOGGEDOUT'] = 'Terminou a autenticação.  Se se deseja autenticar novamente insira as suas credenciais abaixo.';
$lang['pt_PT']['Security']['LOSTPASSWORDHEADER'] = 'Password Perdida';
$lang['pt_PT']['Security']['NOTEPAGESECURED'] = 'Esta página é privada. Insira as suas credenciais abaixo para a visualizar.';
$lang['pt_PT']['Security']['NOTERESETPASSWORD'] = 'Insira o seu endereço de email, e será enviado um link que poderá utilizar para recuperar a sua password';
$lang['pt_PT']['Security']['PASSWORDSENTHEADER'] = 'Link de recuperação da password enviado para \'%s\'';
$lang['pt_PT']['Security']['PASSWORDSENTTEXT'] = 'Obrigado!, O link de recuperação da password foi enviado para \'%s\'.';
$lang['pt_PT']['SimpleImageField']['NOUPLOAD'] = 'Nenhuma imagem enviada';
$lang['pt_PT']['SiteTree']['ACCESSANYONE'] = 'Todos';
$lang['pt_PT']['SiteTree']['ACCESSHEADER'] = 'Quem pode ver esta página no site?';
$lang['pt_PT']['SiteTree']['ACCESSLOGGEDIN'] = 'Utilizadores Autenticados';
$lang['pt_PT']['SiteTree']['ACCESSONLYTHESE'] = 'Apenas estes (Seleccione da lista abaixo)';
$lang['pt_PT']['SiteTree']['ADDEDTODRAFT'] = 'Adicionada ao site de rascunho';
$lang['pt_PT']['SiteTree']['ALLOWCOMMENTS'] = 'Permitir comentários nesta página?';
$lang['pt_PT']['SiteTree']['APPEARSVIRTUALPAGES'] = 'Este conteúdo também aparece nas páginas virtuais nas secções %s.';
$lang['pt_PT']['SiteTree']['BUTTONCANCELDRAFT'] = 'Cancelar alterações do rascunho';
$lang['pt_PT']['SiteTree']['BUTTONCANCELDRAFTDESC'] = 'Apagar o rascunho e reverter para a página actualmente publicada';
$lang['pt_PT']['SiteTree']['BUTTONSAVEPUBLISH'] = 'Gravar e Publicar';
$lang['pt_PT']['SiteTree']['BUTTONUNPUBLISH'] = 'Remover do site Publicado';
$lang['pt_PT']['SiteTree']['BUTTONUNPUBLISHDESC'] = 'Remover esta página do site publicado';
$lang['pt_PT']['SiteTree']['EDITANYONE'] = 'Todos que possam efectuar a autenticação no CMS';
$lang['pt_PT']['SiteTree']['EDITHEADER'] = 'Quem pode editar esta página no CMS?';
$lang['pt_PT']['SiteTree']['EDITONLYTHESE'] = 'Apenas estes (Seleccione da lista abaixo)';
$lang['pt_PT']['SiteTree']['GROUP'] = 'Grupo';
$lang['pt_PT']['SiteTree']['HASBROKENLINKS'] = 'Esta página contém links quebrados.';
$lang['pt_PT']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'Domínio(s)';
$lang['pt_PT']['SiteTree']['HTMLEDITORTITLE'] = 'Conteúdo';
$lang['pt_PT']['SiteTree']['LINKSALREADYUNIQUE'] = '%s já é único';
$lang['pt_PT']['SiteTree']['LINKSCHANGEDTO'] = 'alterado %s -> %s';
$lang['pt_PT']['SiteTree']['MENUTITLE'] = 'Nome na Navegação';
$lang['pt_PT']['SiteTree']['METAADVANCEDHEADER'] = 'Opções Avançadas...';
$lang['pt_PT']['SiteTree']['METADESC'] = 'Descrição';
$lang['pt_PT']['SiteTree']['METAEXTRA'] = 'Meta-Tags personalizáveis';
$lang['pt_PT']['SiteTree']['METAHEADER'] = 'Meta-Tags para motor de Busca';
$lang['pt_PT']['SiteTree']['METAKEYWORDS'] = 'Palavras chave';
$lang['pt_PT']['SiteTree']['METANOTEPRIORITY'] = 'Especificar manualmente uma prioridade do Sitemap do Google para esta página (%s)';
$lang['pt_PT']['SiteTree']['METAPAGEPRIO'] = 'Prioridade da página';
$lang['pt_PT']['SiteTree']['METATITLE'] = 'Título';
$lang['pt_PT']['SiteTree']['MODIFIEDONDRAFT'] = 'Modificada no site de rascunho';
$lang['pt_PT']['SiteTree']['NOBACKLINKS'] = 'Não existe nenhuma página com links para esta.';
$lang['pt_PT']['SiteTree']['NOTEUSEASHOMEPAGE'] = 'Usar esta página como a página pré-definida para os seguintes domínios: 
							(separar múltiplos domínios por vírgulas)';
$lang['pt_PT']['SiteTree']['PAGESLINKING'] = 'A seguintes páginas contêm links para esta página:';
$lang['pt_PT']['SiteTree']['PAGETITLE'] = 'Nome da página';
$lang['pt_PT']['SiteTree']['PAGETYPE'] = 'Tipo de Página';
$lang['pt_PT']['SiteTree']['PRIORITYLEASTIMPORTANT'] = 'Pouca Importância';
$lang['pt_PT']['SiteTree']['PRIORITYMOSTIMPORTANT'] = 'Maior Importância';
$lang['pt_PT']['SiteTree']['PRIORITYNOTINDEXED'] = 'Não indexado';
$lang['pt_PT']['SiteTree']['REMOVEDFROMDRAFT'] = 'Removida do site de rascunho';
$lang['pt_PT']['SiteTree']['SHOWINMENUS'] = 'Mostrar no Menu?';
$lang['pt_PT']['SiteTree']['SHOWINSEARCH'] = 'Mostrar nas pesquisas?';
$lang['pt_PT']['SiteTree']['TABACCESS'] = 'Acesso';
$lang['pt_PT']['SiteTree']['TABBACKLINKS'] = 'Referências';
$lang['pt_PT']['SiteTree']['TABBEHAVIOUR'] = 'Comportamento';
$lang['pt_PT']['SiteTree']['TABCONTENT'] = 'Conteúdo';
$lang['pt_PT']['SiteTree']['TABMAIN'] = 'Principal';
$lang['pt_PT']['SiteTree']['TABMETA'] = 'Meta-data';
$lang['pt_PT']['SiteTree']['TABREPORTS'] = 'Relatórios';
$lang['pt_PT']['SiteTree']['TOPLEVEL'] = 'Conteúdo do Site (Nível Superior)';
$lang['pt_PT']['SiteTree']['URL'] = 'URL';
$lang['pt_PT']['SiteTree']['VALIDATIONURLSEGMENT1'] = 'Outra página já está a utilizar este URL. O URL deve ser único para cada página';
$lang['pt_PT']['SiteTree']['VALIDATIONURLSEGMENT2'] = 'URL\'s só podem conter letras, dígitos e hífens.';
$lang['pt_PT']['TableField']['ISREQUIRED'] = 'No %s \'%s\' é obrigatório.';
$lang['pt_PT']['TableField.ss']['CSVEXPORT'] = 'Exportar para CSV';
$lang['pt_PT']['ToggleCompositeField.ss']['HIDE'] = 'Esconder';
$lang['pt_PT']['ToggleCompositeField.ss']['SHOW'] = 'Mostrar';
$lang['pt_PT']['ToggleField']['LESS'] = 'menos';
$lang['pt_PT']['ToggleField']['MORE'] = 'mais';
$lang['pt_PT']['TypeDropdown']['NONE'] = 'Nenhum';
$lang['pt_PT']['VirtualPage']['CHOOSE'] = 'Escolha uma página para onde redireccionar';
$lang['pt_PT']['VirtualPage']['EDITCONTENT'] = 'clique aqui para editar o conteúdo';
$lang['pt_PT']['VirtualPage']['HEADER'] = 'Esta é uma página virtual';

// --- New New New 

// SiteTree.php
$lang['pt_PT']['SiteTree']['CHANGETO'] = 'Mudar para';
$lang['pt_PT']['SiteTree']['CURRENTLY'] = 'Actualmente';
$lang['pt_PT']['SiteTree']['CURRENT'] = 'Actual';

$lang['pt_PT']['Page']['SINGULARNAME'] = 'Página';
$lang['pt_PT']['Page']['PLURALNAME'] = 'Páginas';
$lang['pt_PT']['ErrorPage']['SINGULARNAME'] = 'Página de Erro';
$lang['pt_PT']['ErrorPage']['PLURALNAME'] = 'Páginas de Erro';
$lang['pt_PT']['UserDefinedForm']['SINGULARNAME'] = 'Formulário Definido pelo Utilizador';
$lang['pt_PT']['UserDefinedForm']['PLURALNAME'] = 'Formulários Definidos pelo Utilizador';
$lang['pt_PT']['RedirectorPage']['SINGULARNAME'] = 'Página de Redireccionamento';
$lang['pt_PT']['RedirectorPage']['PLURALNAME'] = 'Páginas de Redireccionamento';
$lang['pt_PT']['VirtualPage']['SINGULARNAME'] = 'Página Virtual';
$lang['pt_PT']['VirtualPage']['PLURALNAME'] = 'Páginas Virtuais';
$lang['pt_PT']['SubscribeForm']['SINGULARNAME'] = 'Página de Subscrição';
$lang['pt_PT']['SubscribeForm']['PLURALNAME'] = 'Páginas de Subscrição';

// --- New New New New

// forms/TreeSelectorField.php
$lang['pt_PT']['TreeSelectorField']['SAVE'] = 'gravar';
$lang['pt_PT']['TreeSelectorField']['CANCEL'] = 'cancelar';

// forms/NumericField.php
$lang['pt_PT']['NumericField']['VALIDATIONJS'] = 'não é um número. Apenas números podem ser inseridos neste campo';

// forms/HtmlEditorField.php
$lang['pt_PT']['HtmlEditorField']['CLOSE'] = 'fechar';
$lang['pt_PT']['HtmlEditorField']['LINK'] = 'Link';
$lang['pt_PT']['HtmlEditorField']['IMAGE'] = 'Imagem';
$lang['pt_PT']['HtmlEditorField']['FLASH'] = 'Flash';

// forms/GSTNumberField.php
$lang['pt_PT']['GSTNumberField']['VALIDATIONJS'] = 'Por favor insira um número GST válido';

// forms/Form.php
$lang['pt_PT']['Form']['VALIDATOR'] = 'Validador';

// forms/FormField.php
$lang['pt_PT']['FormField']['NONE'] = 'nenhum';

// forms/EmailField.php
$lang['pt_PT']['EmailField']['VALIDATIONJS'] = 'Por favor insira um endereço de email.';

// forms/EditableTextField.php
$lang['pt_PT']['EditableTextField']['TEXTBOXLENGTH'] = 'Tamanho da caixa de texto';
$lang['pt_PT']['EditableTextField']['TEXTLENGTH'] = 'Comprimento do texto';
$lang['pt_PT']['EditableTextField']['NUMBERROWS'] = 'Número de linhas';
$lang['pt_PT']['EditableTextField']['DEFAULTTEXT'] = 'Texto pré-definido';

// forms/EditableFormField.php
$lang['pt_PT']['EditableFormField']['ENTERQUESTION'] = 'Insira a questão';
$lang['pt_PT']['EditableFormField']['REQUIRED'] = 'Obrigatório?';

// forms/EditableEmailField.php
$lang['pt_PT']['EditableEmailField']['SENDCOPY'] = 'Enviar uma cópia do formulário para este email';

// forms/EditableCheckbox.php
$lang['pt_PT']['EditableCheckbox']['ANY'] = 'Qualquer um';
$lang['pt_PT']['EditableCheckbox']['SELECTED'] = 'Seleccionado';
$lang['pt_PT']['EditableCheckbox']['NOTSELECTED'] = 'Não Seleccionado';

// forms/DateField.php
$lang['pt_PT']['DateField']['VALIDATIONJS'] = 'Por favor insira uma data válida (DD/MM/AAAA).';
$lang['pt_PT']['DateField']['NODATESET'] = 'Nenhuma data definida';
$lang['pt_PT']['DateField']['TODAY'] = 'Hoje';
$lang['pt_PT']['DateField']['NOTSET'] = 'Não definido';

// forms/DataReport.php
$lang['pt_PT']['DataReport']['EXPORTCSV'] = 'Exportar para CSV';

// forms/CurrencyField.php
$lang['pt_PT']['CurrencyField']['VALIDATIONJS'] = 'Por favor insira um valor monetário correcto.';
$lang['pt_PT']['CurrencyField']['CURRENCYSYMBOL'] = '€';

// forms/CreditCardField.php
$lang['pt_PT']['CreditCardField']['VALIDATIONJS1'] = 'Por favor certifique-se que inseriu o';
$lang['pt_PT']['CreditCardField']['VALIDATIONJS2'] = 'número correctamente';
$lang['pt_PT']['CreditCardField']['FIRST'] = 'primeiro';
$lang['pt_PT']['CreditCardField']['SECOND'] = 'segundo';
$lang['pt_PT']['CreditCardField']['THIRD'] = 'terceiro';
$lang['pt_PT']['CreditCardField']['FOURTH'] = 'quarto';

// forms/ConfirmedPasswordField.php
$lang['pt_PT']['ConfirmedPasswordField']['HAVETOMATCH'] = 'As passwords teem de coincidir.';
$lang['pt_PT']['ConfirmedPasswordField']['NOEMPTY'] = 'A password não pode estar vazia.';
$lang['pt_PT']['ConfirmedPasswordField']['BETWEEN'] = 'As passwords devem ter entre %s e %s caracteres';
$lang['pt_PT']['ConfirmedPasswordField']['ATLEAST'] = 'As passwords devem ter no mínimo %s caracteres';
$lang['pt_PT']['ConfirmedPasswordField']['MAXIMUM'] = 'As passwords podem ter no máximo %s caracteres';
$lang['pt_PT']['ConfirmedPasswordField']['LEASTONE'] = 'As passwords devem conter pelo menos um numero e um caracter alfanumérico';

// forms/CompositeDateField.php
$lang['pt_PT']['CompositeDateField']['DAY'] = 'Dia';
$lang['pt_PT']['CompositeDateField']['MONTH'] = 'Mês';
$lang['pt_PT']['CompositeDateField']['VALIDATIONJS1'] = 'Por favor certifique-se que tem o';
$lang['pt_PT']['CompositeDateField']['VALIDATIONJS2'] = 'correcto';
$lang['pt_PT']['CompositeDateField']['DAYJS'] = 'dia';
$lang['pt_PT']['CompositeDateField']['MONTHJS'] = 'mês';
$lang['pt_PT']['CompositeDateField']['YEARJS'] = 'ano';

// forms/BankAccountField.php
$lang['pt_PT']['BankAccountField']['VALIDATIONJS'] = 'Por favor, insira um número bancário correcto';

// forms/editor/FieldEditor.php
$lang['pt_PT']['FieldEditor']['EMAILSUBMISSION'] = 'Enviar os dados para o email:';
$lang['pt_PT']['FieldEditor']['EMAILONSUBMIT'] = 'Enviar email após submissão dos dados:';

// parsers/BBCodeParser.php
$lang['pt_PT']['BBCodeParser']['BOLD'] = 'Texto Negrito';
$lang['pt_PT']['BBCodeParser']['BOLDEXAMPLE'] = 'Negrito';
$lang['pt_PT']['BBCodeParser']['ITALIC'] = 'Texto Itálico';
$lang['pt_PT']['BBCodeParser']['ITALICEXAMPLE'] = 'Itálico';
$lang['pt_PT']['BBCodeParser']['UNDERLINE'] = 'Texto Sublinhado';
$lang['pt_PT']['BBCodeParser']['UNDERLINEEXAMPLE'] = 'Sublinhado';
$lang['pt_PT']['BBCodeParser']['STRUCK'] = 'Texto Rasurado';
$lang['pt_PT']['BBCodeParser']['STRUCKEXAMPLE'] = 'Rasurado';
$lang['pt_PT']['BBCodeParser']['COLORED'] = 'Texto Colorido';
$lang['pt_PT']['BBCodeParser']['COLOREDEXAMPLE'] = 'texto azul';
$lang['pt_PT']['BBCodeParser']['ALIGNEMENT'] = 'Alinhamento';
$lang['pt_PT']['BBCodeParser']['ALIGNEMENTEXAMPLE'] = 'alinhado à direita';
$lang['pt_PT']['BBCodeParser']['LINK'] = 'Link';
$lang['pt_PT']['BBCodeParser']['LINKDESCRIPTION'] = 'Link para outro site';
$lang['pt_PT']['BBCodeParser']['EMAILLINK'] = 'Link de Email';
$lang['pt_PT']['BBCodeParser']['EMAILLINKDESCRIPTION'] = 'Criar um link para um endereço de email';
$lang['pt_PT']['BBCodeParser']['IMAGE'] = 'Imagem';
$lang['pt_PT']['BBCodeParser']['IMAGEDESCRIPTION'] = 'Mostrar uma imagem';
$lang['pt_PT']['BBCodeParser']['CODE'] = 'Código';
$lang['pt_PT']['BBCodeParser']['CODEDESCRIPTION'] = 'Bloco de texto não formatado';
$lang['pt_PT']['BBCodeParser']['CODEEXAMPLE'] = 'Bloco de código';
$lang['pt_PT']['BBCodeParser']['UNORDERED'] = 'Lista sem ordenação';
$lang['pt_PT']['BBCodeParser']['UNORDEREDDESCRIPTION'] = 'Lista sem ordenação';
$lang['pt_PT']['BBCodeParser']['UNORDEREDEXAMPLE1'] = 'item sem ordenação 1';
$lang['pt_PT']['BBCodeParser']['UNORDEREDEXAMPLE2'] = 'item sem ordenação 2';

// search/AdvancedSearchForm.php
$lang['pt_PT']['AdvancedSearchForm']['SEARCHBY'] = 'PROCURAR POR';
$lang['pt_PT']['AdvancedSearchForm']['ALLWORDS'] = 'Todas as palavras';
$lang['pt_PT']['AdvancedSearchForm']['EXACT'] = 'Frase exacta';
$lang['pt_PT']['AdvancedSearchForm']['ATLEAST'] = 'Pelo menos uma das palavras';
$lang['pt_PT']['AdvancedSearchForm']['WITHOUT'] = 'Sem as palavras';
$lang['pt_PT']['AdvancedSearchForm']['SORTBY'] = 'ORDENAR POR';
$lang['pt_PT']['AdvancedSearchForm']['RELEVANCE'] = 'Relevância';
$lang['pt_PT']['AdvancedSearchForm']['LASTUPDATED'] = 'Última actualização';
$lang['pt_PT']['AdvancedSearchForm']['PAGETITLE'] = 'Título da Página';
$lang['pt_PT']['AdvancedSearchForm']['LASTUPDATEDHEADER'] = 'ÚLTIMA ACTUALIZAÇÂO';
$lang['pt_PT']['AdvancedSearchForm']['FROM'] = 'De';
$lang['pt_PT']['AdvancedSearchForm']['TO'] = 'Até';
$lang['pt_PT']['AdvancedSearchForm']['GO'] = 'Ir';

// search/SearchForm.php
$lang['pt_PT']['SearchForm']['SEARCH'] = 'Procurar';
$lang['pt_PT']['SearchForm']['GO'] = 'Ir';

// security/Security.php
$lang['pt_PT']['Security']['LOGIN'] = 'Autenticação';
$lang['pt_PT']['Security']['PERMFAILURE'] = 'Esta página requer autenticação e previlégios de administrador.
Insira as sua credenciais abaixo para continuar.';
$lang['pt_PT']['Security']['ENCDISABLED1'] = 'Encriptação de passwords desligada!';
$lang['pt_PT']['Security']['ENCDISABLED2'] = 'Para encriptas as passwords, insira a seguinte linha';
$lang['pt_PT']['Security']['ENCDISABLED3'] = 'em mysite/_config.php';
$lang['pt_PT']['Security']['NOTHINGTOENCRYPT1'] = 'Sem passwords para encriptar';
$lang['pt_PT']['Security']['NOTHINGTOENCRYPT2'] = 'Todos os membros teem as passwords encriptadas!';
$lang['pt_PT']['Security']['ENCRYPT'] = 'Encriptar todas as passwords';
$lang['pt_PT']['Security']['ENCRYPTWITH'] = 'As passwords serão encriptadas com o algoritmo &quot;%s&quot;';
$lang['pt_PT']['Security']['ENCRYPTWITHSALT'] = 'com uma chave para aumentar a segurança';
$lang['pt_PT']['Security']['ENCRYPTWITHOUTSALT'] = 'sem chave para aumentar a segurança';
$lang['pt_PT']['Security']['ENCRYPTEDMEMBERS'] = 'Password encriptada para o membro';
$lang['pt_PT']['Security']['EMAIL'] = 'Email:';
$lang['pt_PT']['Security']['ID'] = 'ID:';

// security/Permission.php
$lang['pt_PT']['Permission']['FULLADMINRIGHTS'] = 'Previlégios de Administrador';
$lang['pt_PT']['Permission']['PERMSDEFINED'] = 'Estão definidas as seguintes permissões';

// core/model/Translatable.php
$lang['pt_PT']['Translatable']['TRANSLATIONS'] = 'Traduções';
$lang['pt_PT']['Translatable']['CREATE'] = 'Criar nova tradução';
$lang['pt_PT']['Translatable']['NEWLANGUAGE'] = 'Nova Língua';
$lang['pt_PT']['Translatable']['CREATEBUTTON'] = 'Criar';
$lang['pt_PT']['Translatable']['EXISTING'] = 'Traduções existentes';

// core/model/SiteTree.php
$lang['pt_PT']['SiteTree']['DEFAULTHOMETITLE'] = 'Início';
$lang['pt_PT']['SiteTree']['DEFAULTHOMECONTENT'] = '<p>Bem-vindo ao Silverstripe! Esta é a página inicial pré-definida. Pode editar esta página no <a href=\"admin/\">CMS</a>. Pode vêr a <a href=\"http://doc.silverstripe.com\">documentação de desenvolvimento</a>, ou os <a href=\"http://doc.silverstripe.com/doku.php?id=tutorials\">tutoriais.</a></p>';
$lang['pt_PT']['SiteTree']['DEFAULTABOUTTITLE'] = 'Sobre';
$lang['pt_PT']['SiteTree']['DEFAULTABOUTCONTENT'] = '<p>Pode inserir o seu conteúdo nesta página ou apaga-la e criar novas.</p>';
$lang['pt_PT']['SiteTree']['DEFAULTCONTACTTITLE'] = 'Contacte-nos';
$lang['pt_PT']['SiteTree']['DEFAULTCONTACTCONTENT'] = '<p>Pode inserir o seu conteúdo nesta página ou apaga-la e criar novas.</p>';

// core/model/ErrorPage.php
$lang['pt_PT']['ErrorPage']['DEFAULTERRORPAGETITLE'] = 'Página de Erro';
$lang['pt_PT']['ErrorPage']['DEFAULTERRORPAGECONTENT'] = '<p>Pedimos desculpa, mas aparentemente tentou aceder a uma página que não existe.</p><p>Verifique o URL que utilizou e tente novamente.</p>';
$lang['pt_PT']['ErrorPage']['404'] = '404 - Página não encontrada';
$lang['pt_PT']['ErrorPage']['500'] = '500 - Erro do servidor';

// SubmittedFormReportField.ss
$lang['pt_PT']['SubmittedFormReportField.ss']['SUBMITTED'] = 'Inserido em';

// RelationComplexTableField.ss
$lang['pt_PT']['RelationComplexTableField.ss']['ADD'] = 'Adicionar';
$lang['pt_PT']['RelationComplexTableField.ss']['SHOW'] = 'Mostrar';
$lang['pt_PT']['RelationComplexTableField.ss']['EDIT'] = 'Editar';
$lang['pt_PT']['RelationComplexTableField.ss']['DELETE'] = 'Apagar';
$lang['pt_PT']['RelationComplexTableField.ss']['NOTFOUND'] = 'Nenhum item encontrado';

// FieldEditor.ss
$lang['pt_PT']['FieldEditor.ss']['ADD'] = 'Adicionar';
$lang['pt_PT']['FieldEditor.ss']['TEXTTITLE'] = 'Adicionar campo de texto';
$lang['pt_PT']['FieldEditor.ss']['TEXT'] = 'Texto';
$lang['pt_PT']['FieldEditor.ss']['CHECKBOXTITLE'] = 'Adicionar caixa de tick';
$lang['pt_PT']['FieldEditor.ss']['CHECKBOX'] = 'Caixa de Tick';
$lang['pt_PT']['FieldEditor.ss']['DROPDOWNTITLE'] = 'Adicionar caixa de selecção';
$lang['pt_PT']['FieldEditor.ss']['DROPDOWN'] = 'Caixa de selecção';
$lang['pt_PT']['FieldEditor.ss']['RADIOSETTITLE'] = 'Adicionar conjunto de botões de rádio';
$lang['pt_PT']['FieldEditor.ss']['RADIOSET'] = 'Conjunto de Botões de Rádio';
$lang['pt_PT']['FieldEditor.ss']['EMAILTITLE'] = 'Adicionar campo de email';
$lang['pt_PT']['FieldEditor.ss']['EMAIL'] = 'Campo de email';
$lang['pt_PT']['FieldEditor.ss']['FORMHEADINGTITLE'] = 'Adicionar cabeçalho';
$lang['pt_PT']['FieldEditor.ss']['FORMHEADING'] = 'Cabeçalho';
$lang['pt_PT']['FieldEditor.ss']['DATETITLE'] = 'Adicionar Campo de Data';
$lang['pt_PT']['FieldEditor.ss']['DATE'] = 'Data';
$lang['pt_PT']['FieldEditor.ss']['FILETITLE'] = 'Adicionar Campo de envio de ficheiro';
$lang['pt_PT']['FieldEditor.ss']['FILE'] = 'Ficheiro';
$lang['pt_PT']['FieldEditor.ss']['CHECKBOXGROUPTITLE'] = 'Adicionar Grupo de caixas de tick';
$lang['pt_PT']['FieldEditor.ss']['CHECKBOXGROUP'] = 'Grupo de Caixas de tick';
$lang['pt_PT']['FieldEditor.ss']['MEMBERTITLE'] = 'Adicionar Selecção de Membros';
$lang['pt_PT']['FieldEditor.ss']['MEMBER'] = 'Selecção de Membros';

// EditableTextField.ss
$lang['pt_PT']['EditableTextField.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableTextField.ss']['TEXTFIELD'] = 'Campo de texto';
$lang['pt_PT']['EditableTextField.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableTextField.ss']['DELETE'] = 'Apagar este campo';

// EditableRadioOption.ss
$lang['pt_PT']['EditableRadioOption.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableRadioOption.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableRadioOption.ss']['LOCKED'] = 'Estes campos não podem ser alterados';

// EditableRadioField.ss
$lang['pt_PT']['EditableRadioField.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableRadioField.ss']['LOCKED'] = 'Estes campos não podem ser alterados';
$lang['pt_PT']['EditableRadioField.ss']['SET'] = 'Conjunto de botões de rádio';
$lang['pt_PT']['EditableRadioField.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableRadioField.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableRadioField.ss']['REQUIRED'] = 'Este campo é obrigatório para este formulário e não pode ser apagado.';
$lang['pt_PT']['EditableRadioField.ss']['ADD'] = 'Adicionar opção';

// EditableFormHeading.ss
$lang['pt_PT']['EditableFormHeading.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableFormHeading.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableFormHeading.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableFormHeading.ss']['HEADING'] = 'Cabeçalho';

// EditableFormField.ss
$lang['pt_PT']['EditableFormField.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableFormField.ss']['LOCKED'] = 'Estes campos não podem ser alterados';
$lang['pt_PT']['EditableFormField.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableFormField.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableFormField.ss']['REQUIRED'] = 'Este campo é obrigatório para este formulário e não pode ser apagado.';

// EditableRadioOption.ss
$lang['pt_PT']['EditableFormFieldOption.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableFormFieldOption.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableFormFieldOption.ss']['LOCKED'] = 'Estes campos não podem ser alterados';

// EditableFileField.ss
$lang['pt_PT']['EditableFileField.ss']['DRAG'] = 'Arraste para reordenar os campos';

// EditableFileField.ss
$lang['pt_PT']['EditableFileField.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableFileField.ss']['FILE'] = 'Campo de envio de ficheiro';
$lang['pt_PT']['EditableFileField.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableFileField.ss']['DELETE'] = 'Remover esta opção';

// EditableEmailField.ss
$lang['pt_PT']['EditableEmailField.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableEmailField.ss']['EMAIL'] = 'Campo de email';
$lang['pt_PT']['EditableEmailField.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableEmailField.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableEmailField.ss']['REQUIRED'] = 'Este campo é obrigatório para este formulário e não pode ser apagado.';

// EditableDropdown.ss
$lang['pt_PT']['EditableDropdown.ss']['LOCKED'] = 'Estes campos não podem ser alterados';
$lang['pt_PT']['EditableDropdown.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableDropdown.ss']['DROPDOWN'] = 'Lista de Selecção';
$lang['pt_PT']['EditableDropdown.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableDropdown.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableDropdown.ss']['REQUIRED'] = 'Este campo é obrigatório para este formulário e não pode ser apagado.';

// EditableDropdownOption.ss
$lang['pt_PT']['EditableDropdownOption.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableDropdownOption.ss']['LOCKED'] = 'Estes campos não podem ser alterados';
$lang['pt_PT']['EditableDropdownOption.ss']['DELETE'] = 'Remover esta opção';

// EditableDateField.ss
$lang['pt_PT']['EditableDateField.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableDateField.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableDateField.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableDateField.ss']['DATE'] = 'Campo de Data';

// EditableCheckbox.ss
$lang['pt_PT']['EditableCheckbox.ss']['LOCKED'] = 'Estes campos não podem ser alterados';
$lang['pt_PT']['EditableCheckbox.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableCheckbox.ss']['CHECKBOX'] = 'Caixa de tick';
$lang['pt_PT']['EditableCheckbox.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableCheckbox.ss']['DELETE'] = 'Remover esta opção';

// EditableCheckboxOption.ss
$lang['pt_PT']['EditableCheckboxOption.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableCheckboxOption.ss']['LOCKED'] = 'Estes campos não podem ser alterados';
$lang['pt_PT']['EditableCheckboxOption.ss']['DELETE'] = 'Remover esta opção';

// EditableCheckboxGroupField.ss
$lang['pt_PT']['EditableCheckboxGroupField.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['DATE'] = 'Campo de Data';

// EditableCheckboxGroupField.ss
$lang['pt_PT']['EditableCheckboxGroupField.ss']['LOCKED'] = 'Estes campos não podem ser alterados';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['DRAG'] = 'Arraste para reordenar os campos';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['CHECKBOXGROUP'] = 'Grupo de Caixas de tick';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['MORE'] = 'Mais opções';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['DELETE'] = 'Remover esta opção';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['REQUIRED'] = 'Este campo é obrigatório para este formulário e não pode ser apagado.';
$lang['pt_PT']['EditableCheckboxGroupField.ss']['ADD'] = 'Adicionar opção';

// ForgotPasswordEmail.ss
$lang['pt_PT']['ForgotPasswordEmail.ss']['TEXT1'] = 'Aqui está o seu';
$lang['pt_PT']['ForgotPasswordEmail.ss']['TEXT2'] = 'link de reset da password';
$lang['pt_PT']['ForgotPasswordEmail.ss']['TEXT3'] = 'para';

// TableListField_PageControls.ss
$lang['pt_PT']['TableListField_PageControls.ss']['VIEWLAST'] = 'Ver último';
$lang['pt_PT']['TableListField_PageControls.ss']['VIEWFIRST'] = 'Ver primeiro';
$lang['pt_PT']['TableListField_PageControls.ss']['VIEWPREVIOUS'] = 'Ver anterior';
$lang['pt_PT']['TableListField_PageControls.ss']['VIEWNEXT'] = 'Ver próximo';
$lang['pt_PT']['TableListField_PageControls.ss']['DISPLAYING'] = 'A Mostrar';
$lang['pt_PT']['TableListField_PageControls.ss']['TO'] = 'até';
$lang['pt_PT']['TableListField_PageControls.ss']['OF'] = 'de';

// New2

$lang['pt_PT']['TableField.ss']['ADD'] = 'Adicionar nova linha';
$lang['pt_PT']['TableField.ss']['ADDITEM'] = 'Adicionar';
$lang['pt_PT']['TableField.ss']['DELETEROW'] = 'Apagar esta linha';
$lang['pt_PT']['TableField.ss']['DELETE'] = 'apagar';

$lang['pt_PT']['Security']['OPENIDHEADER'] = 'Credenciais OpenID/i-name';
$lang['pt_PT']['Security']['MEMBERALREADYEXISTS'] = 'Já existe um utilizador com esta identidade';
$lang['pt_PT']['Security']['OPENIDURL'] = 'OpenID URL/i-name';
$lang['pt_PT']['Security']['OPENIDDESC'] = '<p>Certifique-se que inseriu aqui as suas credenciais OpenID/i-name normalizadas 
				, p.ex. com protocolo e barra para a direita para o OpenID (ex. http://openid.silverstripe.com/).</p>';
$lang['pt_PT']['Security']['EDITOPENIDURL'] = 'OpenID URL/i-name (ex. http://openid.silverstripe.com/)';
$lang['pt_PT']['Security']['OPENIDURLNORMALIZATION'] = '<p>Certifique-se que inseriu aqui as suas credenciais OpenID/i-name normalizadas 
				, p.ex. com protocolo e barra para a direita para o OpenID (ex. http://openid.silverstripe.com/).</p>';

$lang['pt_PT']['TableListField']['CSVEXPORT'] = 'Exportar para CSV';
$lang['pt_PT']['TableListField']['PRINT'] = 'Imprimir';

$lang['pt_PT']['Permission']['FULLADMINRIGHTS'] = 'Permissões de administração total';

$lang['pt_PT']['Page']['CLASSNAME'] = 'Página';

$lang['pt_PT']['Statistics']['TRENDS'] = 'Tendências';
$lang['pt_PT']['Statistics']['LEGEND'] = 'Legenda';
$lang['pt_PT']['Statistics']['BROWSERS'] = 'Browsers';
$lang['pt_PT']['Statistics']['ID'] = 'ID';
$lang['pt_PT']['Statistics']['EMAIL'] = 'Email';
$lang['pt_PT']['Statistics']['JOINED'] = 'Creado em';
$lang['pt_PT']['Statistics']['REGISTEREDUSERS'] = 'Utilizadores Registados';
$lang['pt_PT']['Statistics']['CSVEXPORT'] = 'Exportar como CSV';
$lang['pt_PT']['Statistics']['RECENTPAGEVIEWS'] = 'Visualização Recente de Páginas';
$lang['pt_PT']['Statistics']['TIME'] = 'Data/Hora';
$lang['pt_PT']['Statistics']['BROWSER'] = 'Browser';
$lang['pt_PT']['Statistics']['OSABREV'] = 'SO';
$lang['pt_PT']['Statistics']['USER'] = 'Utilizador';
$lang['pt_PT']['Statistics']['PAGE'] = 'Página';
$lang['pt_PT']['Statistics']['PAGEVIEWS'] = 'Visualizações';
$lang['pt_PT']['Statistics']['OS'] = 'Sistemas Operativos';
$lang['pt_PT']['Statistics']['USERACTIVITY'] = 'Actividade dos Utilizadores';


?>