# Hugging Face Integration Setup

This guide will help you set up Hugging Face AI integration for personalized meal planning.

## Getting Your API Key

1. **Create Account**: Visit [huggingface.co](https://huggingface.co) and create a free account
2. **Generate Token**: Go to [Settings > Access Tokens](https://huggingface.co/settings/tokens)
3. **Create New Token**: Click "New token" and give it a name like "diet-calculator"
4. **Copy Token**: Copy the generated token (starts with `hf_...`)

## Configuration

1. **Environment File**: Open your `.env.local` file
2. **Add Key**: Add this line with your actual token:
   ```
   HUGGINGFACE_API_KEY=hf_your_actual_token_here
   ```
3. **Restart Server**: Stop and restart your development server

## How It Works

### With API Key
- Uses Hugging Face's language models to generate personalized meal plans
- Creates contextual recommendations based on your dietary preferences
- Provides varied and creative meal suggestions

### Without API Key (Fallback)
- Uses intelligent algorithms to create structured meal plans
- Still personalizes based on dietary preferences, allergies, and goals
- Provides nutritionally balanced recommendations

## Supported Models

The default configuration uses **DialoGPT-large** for conversational meal planning. You can switch to other models by modifying `src/lib/huggingface-client.ts`:

### Popular Options:
- `microsoft/DialoGPT-large` (default, good for conversational responses)
- `microsoft/DialoGPT-medium` (faster, smaller responses)
- `bigscience/bloom-560m` (multilingual support)
- `facebook/blenderbot-400M-distill` (conversation focused)

### For Advanced Users:
- `meta-llama/Llama-2-7b-chat-hf` (requires approval)
- `mistralai/Mistral-7B-Instruct-v0.1` (high quality)
- `codellama/CodeLlama-7b-Instruct-hf` (instruction following)

## Customizing the AI Prompt

Edit the `createMealPlanPrompt` function in `src/lib/huggingface-client.ts` to:
- Change the tone of responses
- Request specific information formats
- Add new dietary considerations
- Include regional cuisine preferences

## Rate Limits

Hugging Face free tier includes:
- **1,000 requests/month** for most models
- **Rate limit**: ~1 request per second
- **Response time**: 2-10 seconds depending on model

For production use, consider:
- Hugging Face Pro ($20/month) for higher limits
- Hugging Face Enterprise for dedicated resources
- Local deployment using Hugging Face Transformers

## Troubleshooting

### Common Issues:

1. **"Model is loading" error**:
   - Wait 30-60 seconds and retry
   - Some models need "cold start" time

2. **Rate limit exceeded**:
   - Wait a few minutes between requests
   - Consider upgrading to Pro plan

3. **Invalid API key**:
   - Verify the token starts with `hf_`
   - Ensure no extra spaces in .env file
   - Regenerate token if needed

4. **Poor meal plan quality**:
   - Try different models
   - Adjust the prompt in `huggingface-client.ts`
   - Increase `max_new_tokens` for longer responses

### Debugging:
- Check browser console for API errors
- Review server logs for detailed error messages
- Test API key directly on Hugging Face website

## Model Comparison

| Model | Speed | Quality | Use Case |
|-------|-------|---------|----------|
| DialoGPT-large | Medium | Good | Conversational meal planning |
| DialoGPT-medium | Fast | Moderate | Quick responses |
| Bloom-560m | Fast | Good | Multilingual support |
| Llama-2-7b | Slow | Excellent | Detailed meal plans |

## Getting Help

- Check the [Hugging Face documentation](https://huggingface.co/docs)
- Visit the [Hugging Face community](https://huggingface.co/join/discord)
- Create an issue in this repository

---

**Pro Tip**: Start with the free tier to test the integration, then upgrade based on your usage needs!