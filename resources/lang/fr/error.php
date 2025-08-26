<?php
return [
    //系统常用
    'lang'   => 'fr',
    '网络延迟' => 'Retard du réseau',
    '参数错误' => 'Erreur de paramètre',
    '操作频繁' => 'Opérations fréquentes',
    '网络异常' => 'Anomalie du réseau',
    '数据异常' => 'Anomalie de données',
    '成功'    => 'Succès',
    '数据不存在' => "Les données n'existent pas",
    '状态异常' => 'Statut anormal',
    '非法请求' => 'Demande illégale',
    '系统维护' => 'Entretien du système',
    
    //登录
    '请通过邀请链接注册登录' => "Veuillez vous inscrire et vous connecter via le lien d'invitation",
    '请输入钱包地址' => "Veuillez saisir l'adresse du portefeuille",
    '钱包地址不能为空' => "L'adresse du portefeuille ne peut pas être vide",
    '钱包地址有误'    => 'Mauvaise adresse de portefeuille',
    '用户已被禁止登录' => "L'utilisateur a été interdit de se connecter",
    '请填写邀请码'    => "Veuillez remplir le code d'invitation",
    '邀请码不存在'    => "Le code d'invitation n'existe pas",
    '推荐人不存在'    => "Le recommandateur n'existe pas",
    
    //上传
    '请选择文件'    => "Veuillez sélectionner un fichier",
    '格式错误'    => 'Erreur de format',
    '图片大小有误'    => "La taille de l'image est incorrecte",
    
    //提币
    '余额不足' => 'Solde insuffisant',
    '提币数量不能为0' => 'Le montant des pièces retirées ne peut pas être égal à 0',
    '提币状态1' => "En attente d'examen",
    '提币状态2' => 'Succès',
    '提币状态3' => 'échouer',
    '每日提币次数' => "Les retraits peuvent être effectués jusqu'à %s fois par jour",
    'withdrawal_min' => 'Retrait minimum de %s pièces',
    
    //PK
    '敬请期待' => "Restez à l'écoute",
    
    '提币数量错误' => 'Mauvaise quantité de pièces à retirer',
    '提币数量整倍数' => 'Le montant du retrait est un multiple de %s',
    '请选择倍数' => 'Veuillez en sélectionner plusieurs',
    '抽奖次数不足' => 'Pas assez de tirages',
    '请选择签到价格' => "Veuillez sélectionner le prix d'enregistrement",
    '未到下一次签到时间' => "L'heure de connexion suivante n'est pas encore arrivée",
    
    '只能指定伞下用户' => 'Seuls les utilisateurs sous parapluie peuvent être spécifiés',
    '不能超过自己级别' => 'Ne peut pas dépasser son propre niveau',
    '此用户不可操作'  => "Cet utilisateur n'est pas opérationnel",
    '您已经是商家' => 'Vous êtes déjà marchand',
    '您不是商家' => "Vous n'êtes pas un commerçant",
    '请选择购买数量' => 'Veuillez sélectionner la quantité à acheter',
    '请输入赠送数量' => 'Veuillez entrer le nombre de cadeaux',
    '赠送用户不存在' => "L'utilisateur cadeau n'existe pas",
    '不能赠送给自己' => 'Ne peut pas se donner à soi - même',

    '您已经是普通节点' => 'Vous êtes déjà un nœud normal',
    '您已经是超级节点' => 'Vous êtes déjà un super nœud',
    '您不满足开通条件' => "Vous ne remplissez pas les conditions d'activation",
    '需要算力手续费' => 'Nécessite des frais de puissance de calcul de s%',
    
    //分类1系统增加2系统扣除3余额提币4提币驳回5普通节点获得6管理级奖7超级节点获得
    'USDT类型1' => 'Ajouts au système',
    'USDT类型2' => 'Déduction du système',
    'USDT类型3' => 'Retrait du solde',
    'USDT类型4' => 'Retrait rejeté',
    'USDT类型5' => 'Nœud ordinaire',
    'USDT类型6' => 'Prix niveau Management',
    'USDT类型7' => 'Super nœud',
    
    //分类1系统增加2系统扣除3余额提币4提币驳回5签到获得6推荐加速7见点加速8团队加速9幸运抽奖
    'DOGBEE类型1' => 'Ajouts au système',
    'DOGBEE类型2' => 'Déduction du système',
    'DOGBEE类型3' => 'Retrait du solde',
    'DOGBEE类型4' => 'Retrait rejeté',
    'DOGBEE类型5' => 'Connectez-vous pour obtenir',
    'DOGBEE类型6' => 'Accélération recommandée',
    'DOGBEE类型7' => "Voir l'accélération ponctuelle",
    'DOGBEE类型8' => "Accélération d'équipe",
    'DOGBEE类型9' => 'Tirage au sort',
    
    //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速9积分兑换
    'POWER类型1' => 'Ajouts au système',
    'POWER类型2' => 'Déduction du système',
    'POWER类型3' => 'Retrait du solde',
    'POWER类型4' => 'Retrait rejeté',
    'POWER类型5' => 'Connexion en déduction',
    'POWER类型6' => 'Accélération recommandée',
    'POWER类型7' => "Voir l'accélération ponctuelle",
    'POWER类型8' => "Accélération d'équipe",
    'POWER类型9' => 'Échange de points',
    'POWER类型10' => 'Déduction de retrait',
    'POWER类型11' => 'Retrait rejeté',
    
    //分类1系统增加2系统扣除3开通商家4购买积分5赠送扣除6赠送获得7兑换算力
    'POINT类型1' => 'Ajouts au système',
    'POINT类型2' => 'Déduction du système',
    'POINT类型3' => 'Ouvrir un commerçant',
    'POINT类型4' => 'Acheter des points',
    'POINT类型5' => 'Déduction pour don',
    'POINT类型6' => 'Cadeau obtenir',
    'POINT类型7' => 'Convertir le pouvoir',
];
