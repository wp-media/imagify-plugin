export const meta = {
  name: 'maestro-quality-gates',
  description: 'Run DOD L2, lead review, and QA quality gates for a maestro pipeline PR',
  phases: [
    { title: 'Quality Gates' },
  ],
}

// args shape — assembled and passed by the orchestrator after Step 6:
// {
//   issueN             string  — issue number
//   prUrl              string  — full GitHub PR URL
//   prNumber           number  — PR number
//   branch             string  — feature branch name
//   baseBranch         string  — e.g. 'origin/develop'
//   tempRoot           string  — e.g. '.ai'
//   specPath           string  — absolute path to spec.md
//   acceptanceCriteria string  — numbered list of acceptance criteria
//   domains            string  — 'backend' | 'frontend' | 'both'
//   uiVisible          boolean — true when PHP renders visible admin output
//   executionMode      string  — 'parallel' | 'sequential'
//   skipLeadReview     boolean — true for XS+LOW issues
//   skipQa             boolean — true for purely internal refactors
//   sessionLearnings   string  — content of AGENTS.md section 13
//   currentModel       string  — display name of the running model, e.g. "Claude Sonnet 4.6"
// }

const DOD_SCHEMA = {
  type: 'object',
  required: ['overall', 'checks', 'blockers', 'warnings', 'layer1_delta'],
  properties: {
    overall: { type: 'string', enum: ['PASS', 'WARN', 'FAIL'] },
    checks: { type: 'array', items: { type: 'object' } },
    blockers: { type: 'array', items: { type: 'object' } },
    warnings: { type: 'array', items: { type: 'string' } },
    layer1_delta: { type: 'array', items: { type: 'string' } },
  },
}

const REVIEW_SCHEMA = {
  type: 'object',
  required: ['pr_url', 'verdict', 'inline_comments_posted', 'pr_commented', 'blockers', 'nice_to_haves', 'summary'],
  properties: {
    pr_url: { type: 'string' },
    verdict: { type: 'string', enum: ['PASS', 'REQUEST_CHANGES'] },
    inline_comments_posted: { type: 'boolean' },
    pr_commented: { type: 'boolean' },
    blockers: { type: 'array', items: { type: 'object' } },
    nice_to_haves: { type: 'array', items: { type: 'object' } },
    summary: { type: 'string' },
  },
}

const QA_SCHEMA = {
  type: 'object',
  required: ['overall', 'pr_commented', 'criteria_results', 'smoke_tests', 'blockers'],
  properties: {
    overall: { type: 'string', enum: ['PASS', 'FAIL', 'PARTIAL'] },
    strategies_used: { type: 'array', items: { type: 'string' } },
    pr_commented: { type: 'boolean' },
    criteria_results: { type: 'array', items: { type: 'object' } },
    smoke_tests: { type: 'array', items: { type: 'object' } },
    tests_authored: { type: 'array', items: { type: 'string' } },
    pr_comment_url: { type: 'string' },
    blockers: { type: 'array', items: { type: 'string' } },
    recommendations: { type: 'array', items: { type: 'object' } },
  },
}

const {
  issueN, prUrl, prNumber, branch, baseBranch, tempRoot, specPath,
  acceptanceCriteria, domains, uiVisible,
  executionMode, skipLeadReview, skipQa,
  sessionLearnings, currentModel,
} = args

const dodPrompt = [
  `You are running the Definition of Done Layer 2 (independent gate) for issue #${issueN}.`,
  '',
  `PR: ${prUrl}`,
  `PR number: ${prNumber}`,
  `Branch: ${branch}`,
  `Base branch: ${baseBranch}`,
  `Temp root: ${tempRoot}`,
  '',
  'Read .claude/commands/dod.md for the complete check instructions, then run all Layer 2 checks.',
  'This is a fresh, independent read — do not assume Layer 1 caught everything.',
].join('\n')

const reviewPrompt = [
  `You are the lead-reviewer for issue #${issueN}.`,
  '',
  `PR: ${prUrl}`,
  `Spec: ${specPath}`,
  `Base branch: ${baseBranch}`,
  '',
  'Acceptance criteria:',
  acceptanceCriteria,
  '',
  'Session learnings (AGENTS.md §13):',
  sessionLearnings || '(none)',
  `Current model: ${currentModel}`,
  '',
  'Review the PR diff against the spec. Post inline comments on GitHub for every blocker.',
  'Post a summary comment on the PR.',
].join('\n')

const uiNote = uiVisible
  ? 'Strategy B (browser/visual) is the PRIMARY strategy — UI changes are present.'
  : ''

const qaPrompt = [
  `You are the qa-engineer for issue #${issueN}.`,
  '',
  `PR number: ${prNumber}`,
  `PR URL: ${prUrl}`,
  `Base branch: ${baseBranch}`,
  `Domains: ${domains}`,
  `UI visible: ${uiVisible}`,
  uiNote,
  '',
  'Acceptance criteria:',
  acceptanceCriteria,
  '',
  'Session learnings (AGENTS.md §13):',
  sessionLearnings || '(none)',
  `Current model: ${currentModel}`,
  '',
  'Test the PR against the acceptance criteria. Post results as a comment on the PR.',
].filter(line => line !== '').join('\n')

const runParallel = executionMode === 'parallel'

let dodResult = null
let reviewResult = null
let qaResult = null

if (runParallel) {
  log('Running quality gates in parallel...')

  const thunks = [
    () => agent(dodPrompt, { label: 'dod-l2', phase: 'Quality Gates', schema: DOD_SCHEMA }),
  ]
  if (!skipLeadReview) {
    thunks.push(
      () => agent(reviewPrompt, { label: 'lead-review', phase: 'Quality Gates', schema: REVIEW_SCHEMA, agentType: 'lead-reviewer' })
    )
  }
  if (!skipQa) {
    thunks.push(
      () => agent(qaPrompt, { label: 'qa', phase: 'Quality Gates', schema: QA_SCHEMA, agentType: 'qa-engineer' })
    )
  }

  const results = await parallel(thunks)
  let idx = 0
  dodResult = results[idx++]
  if (!skipLeadReview) reviewResult = results[idx++]
  if (!skipQa) qaResult = results[idx++]

} else {
  log('Running DOD L2...')
  dodResult = await agent(dodPrompt, { label: 'dod-l2', phase: 'Quality Gates', schema: DOD_SCHEMA })

  if (!skipLeadReview) {
    log('Running lead review...')
    reviewResult = await agent(reviewPrompt, { label: 'lead-review', phase: 'Quality Gates', schema: REVIEW_SCHEMA, agentType: 'lead-reviewer' })
  }

  if (!skipQa) {
    log('Running QA...')
    qaResult = await agent(qaPrompt, { label: 'qa', phase: 'Quality Gates', schema: QA_SCHEMA, agentType: 'qa-engineer' })
  }
}

return { dod: dodResult, review: reviewResult, qa: qaResult }
