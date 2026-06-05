param location string
param environment string
param tags object
param postgresSubnetId string
param privateDnsZoneName string

var storageAccountName = toLower('utst${uniqueString(resourceGroup().id)}')
var keyVaultName = toLower('utkv${uniqueString(resourceGroup().id)}')

@secure()
param postgresAdminPassword string = newGuid()

resource postgres 'Microsoft.DBforPostgreSQL/flexibleServers@2024-08-01' = {
  name: 'understandtech-pg-${environment}'
  location: location
  tags: tags
  sku: {
    name: 'Standard_B2s'
    tier: 'Burstable'
  }
  properties: {
    version: '16'
    administratorLogin: 'moodle_admin'
    administratorLoginPassword: postgresAdminPassword
    storage: {
      storageSizeGB: 32
    }
    backup: {
      backupRetentionDays: 14
      geoRedundantBackup: 'Disabled'
    }
    network: {
      delegatedSubnetResourceId: postgresSubnetId
      privateDnsZoneArmResourceId: privateDnsZone.id
    }
    highAvailability: {
      mode: 'Disabled'
    }
  }
}

resource postgresDb 'Microsoft.DBforPostgreSQL/flexibleServers/databases@2024-08-01' = {
  parent: postgres
  name: 'moodle'
  properties: {
    charset: 'UTF8'
    collation: 'en_US.utf8'
  }
}

resource privateDnsZone 'Microsoft.Network/privateDnsZones@2020-06-01' existing = {
  name: privateDnsZoneName
}

resource redis 'Microsoft.Cache/redisEnterprise@2025-04-01' = {
  name: 'understandtech-redis-${environment}'
  location: location
  tags: tags
  sku: {
    name: 'Balanced_B0'
  }
  properties: {
    encryption: {}
    highAvailability: 'Disabled'
    minimumTlsVersion: '1.2'
  }
}

resource redisDatabase 'Microsoft.Cache/redisEnterprise/databases@2025-04-01' = {
  parent: redis
  name: 'default'
  properties: {
    clientProtocol: 'Encrypted'
    clusteringPolicy: 'OSSCluster'
    evictionPolicy: 'AllKeysLRU'
    modules: []
  }
}

resource storage 'Microsoft.Storage/storageAccounts@2023-05-01' = {
  name: storageAccountName
  location: location
  tags: tags
  sku: {
    name: 'Premium_LRS'
  }
  kind: 'FileStorage'
  properties: {
    supportsHttpsTrafficOnly: true
    minimumTlsVersion: 'TLS1_2'
    allowBlobPublicAccess: false
  }
}

resource fileServices 'Microsoft.Storage/storageAccounts/fileServices@2023-05-01' = {
  parent: storage
  name: 'default'
}

resource fileShare 'Microsoft.Storage/storageAccounts/fileServices/shares@2023-05-01' = {
  parent: fileServices
  name: 'moodledata'
  properties: {
    shareQuota: 100
    enabledProtocols: 'SMB'
  }
}

resource keyVault 'Microsoft.KeyVault/vaults@2023-07-01' = {
  name: keyVaultName
  location: location
  tags: tags
  properties: {
    tenantId: subscription().tenantId
    sku: {
      family: 'A'
      name: 'standard'
    }
    enableRbacAuthorization: true
    enableSoftDelete: true
    softDeleteRetentionInDays: 90
    enablePurgeProtection: true
  }
}

var secretNames = [
  'moodle-db-password'
  'cf-stream-signing-key'
  'cf-worker-shared-secret'
  'anthropic-api-key'
  'openai-api-key'
  'redis-password'
]

resource emptySecrets 'Microsoft.KeyVault/vaults/secrets@2023-07-01' = [for name in secretNames: {
  parent: keyVault
  name: name
  properties: {
    value: 'REPLACE-ME'
  }
}]

output postgresPrivateFqdn string = postgres.properties.fullyQualifiedDomainName
output redisHostName string = redis.properties.hostName
output keyVaultUri string = keyVault.properties.vaultUri
output keyVaultId string = keyVault.id
output keyVaultName string = keyVault.name
output storageAccountName string = storage.name
