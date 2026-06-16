export const meta = {
  name: 'imagify-implementation',
  description: 'Run backend and/or frontend implementation agents for imagify pipeline issue',
  phases: [
    { title: 'Implementation' },
  ],
}

// args shape — assembled and passed by the orchestrator at Step 5:
// {
//   issueN           string  — issue number
//   branch           string  — feature branch name
//   specPath         string  — absolute path to spec.md
//   domains          string  — 'backend' | 'frontend' | 'both'
//   model            string  — 'sonnet' | 'opus' | 'haiku'
//   backendDispatch  string  — full dispatch plan for backend-agent (null if frontend-only)
//   frontendDispatch string  — full dispatch plan for frontend-agent (null if backend-only)
//   worktrees        object  — { backend: path, frontend: path } | null (null if single-domain)
//   sessionLearnings string  — content of AGENTS.md section 13
//   currentModel     string  — display name of the running model, e.g. "Claude Sonnet 4.6"
// }

const IMPL_SCHEMA = {
  type: 'object',
  required: ['ticket_id', 'branch', 'files_changed', 'tests_passing', 'dod_layer1', 'co_authored_by'],
  properties: {
    ticket_id: { type: 'string' },
    branch: { type: 'string' },
    files_changed: { type: 'array', items: { type: 'string' } },
    tests_passing: { type: 'boolean' },
    test_output: { type: 'string' },
    docs: {
      type: 'object',
      required: ['status'],
      properties: {
        status: { type: 'string', enum: ['DONE', 'SKIP'] },
        files_updated: { type: 'array', items: { type: 'string' } },
        files_created: { type: 'array', items: { type: 'string' } },
      },
    },
    dod_layer1: {
      type: 'object',
      required: ['overall', 'checks'],
      properties: {
        overall: { type: 'string', enum: ['PASS', 'WARN', 'FAIL'] },
        checks: { type: 'array', items: { type: 'object' } },
      },
    },
    co_authored_by: { type: 'string' },
    reasoning: {
      type: 'object',
      properties: {
        alternatives_considered: { type: 'array', items: { type: 'string' } },
        hesitations: { type: 'array', items: { type: 'string' } },
        decision_rationale: { type: 'string' },
      },
    },
    notes: { type: 'string' },
  },
}

function buildPrompt(role, dispatch, worktreePath, issueN, branch, specPath, sessionLearnings, currentModel) {
  const lines = [
    `You are the ${role} for issue #${issueN}.`,
    '',
    `Branch: ${branch}`,
    `Spec: ${specPath}`,
  ]
  if (worktreePath) lines.push(`Working directory (git worktree): ${worktreePath}`)
  lines.push(
    '',
    'Dispatch plan:',
    dispatch,
    '',
    'Session learnings (AGENTS.md §13):',
    sessionLearnings || '(none)',
    '',
    `Current model: ${currentModel}`,
    '',
    'Run the docs skill and dod skill (layer 1) inline before committing.',
    'Commit atomically to the branch above.',
  )
  return lines.join('\n')
}

const {
  issueN, branch, specPath,
  domains, model,
  backendDispatch, frontendDispatch,
  worktrees,
  sessionLearnings, currentModel,
} = args

const needsBackend = domains === 'backend' || domains === 'both'
const needsFrontend = domains === 'frontend' || domains === 'both'
const runParallel = domains === 'both'

let backendResult = null
let frontendResult = null

if (runParallel) {
  log('Spawning backend and frontend agents in parallel...')
  const results = await parallel([
    () => agent(
      buildPrompt('backend-agent', backendDispatch, worktrees && worktrees.backend, issueN, branch, specPath, sessionLearnings, currentModel),
      { label: 'backend', phase: 'Implementation', schema: IMPL_SCHEMA, model, agentType: 'backend-agent' }
    ),
    () => agent(
      buildPrompt('frontend-agent', frontendDispatch, worktrees && worktrees.frontend, issueN, branch, specPath, sessionLearnings, currentModel),
      { label: 'frontend', phase: 'Implementation', schema: IMPL_SCHEMA, model, agentType: 'frontend-agent' }
    ),
  ])
  backendResult = results[0]
  frontendResult = results[1]
} else {
  if (needsBackend) {
    log('Running backend agent...')
    backendResult = await agent(
      buildPrompt('backend-agent', backendDispatch, null, issueN, branch, specPath, sessionLearnings, currentModel),
      { label: 'backend', phase: 'Implementation', schema: IMPL_SCHEMA, model, agentType: 'backend-agent' }
    )
  }
  if (needsFrontend) {
    log('Running frontend agent...')
    frontendResult = await agent(
      buildPrompt('frontend-agent', frontendDispatch, null, issueN, branch, specPath, sessionLearnings, currentModel),
      { label: 'frontend', phase: 'Implementation', schema: IMPL_SCHEMA, model, agentType: 'frontend-agent' }
    )
  }
}

return { backend: backendResult, frontend: frontendResult }
