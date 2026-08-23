# Stage 0 — Discovery & Architecture Index

Stage 0 defines what the current Dahim system does, what must be retained, what must be rebuilt, and the architecture of the standalone replacement.

## Checklist

- [x] Foundation and project direction
- [x] Dashboard audit
- [x] Plugin audit
- [x] Theme audit
- [x] Data/database audit
- [x] API/integration audit
- [x] Frontend/UI and route audit
- [x] Security audit
- [x] Infrastructure/deployment audit
- [x] Migration plan
- [x] Final architecture
- [x] Consolidated Stage 0 checkpoint
- [ ] Final human approval

## Target architecture

The platform is standalone. WordPress is a migration source only and will not be required at runtime.

See `../architecture.md` for the target architecture and `00-stage-0-complete.md` for the consolidated Stage 0 decisions.

## GitHub rule

Do not consider a stage complete based solely on conversation. A stage is complete when its documentation and Git commit can be verified in this repository.

## Stage 1 gate

Stage 1 begins only after the Stage 0 completion checkpoint has been reviewed and approved.