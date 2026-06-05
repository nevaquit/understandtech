using '../main.bicep'

param adminIpAddress = '203.0.113.10/32'
param vmAdminUsername = 'azureadmin'
param vmAdminPublicKey = 'ssh-ed25519 AAAA... replace-before-deploy'
param environment = 'prod'
param location = 'eastus2'
