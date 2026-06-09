using 'main.bicep'

// Staging stack — separate RG (understandtech-staging-rg), own PG/Redis/KV.
// DNS: staging.understandtech.app → VM public IP (Cloudflare A record, proxied, Origin Pulls).
param adminIpAddress = '146.70.186.204/32'
param vmAdminUsername = 'azureadmin'
param vmAdminPublicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAILAhcY/cKe3kILtC3+VS0IC8bzEqxIuP03xZgPU258DX nevaquit@gmail.com'
param environment = 'staging'
param location = 'eastus2'
// Cheaper burstable VM; prod uses Standard_D2s_v3.
param vmSize = 'Standard_B2ms'
param vmZone = ''
