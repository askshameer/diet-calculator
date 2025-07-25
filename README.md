# Professional Diet Calculator

A comprehensive web application for personalized diet planning with AI-powered meal recommendations, nutritional calculations, and PDF report generation.

## Features

### Core Functionality
- **Comprehensive Diet Calculator**: Multi-step form capturing all essential dietary information
- **BMR/TDEE Calculations**: Scientific metabolic rate calculations using Mifflin-St Jeor equation
- **AI-Powered Meal Planning**: Personalized meal recommendations based on preferences and restrictions
- **PDF Report Generation**: Professional reports with meal plans, shopping lists, and nutritional breakdowns
- **Progress Tracking**: User accounts with historical data and progress monitoring

### Detailed Input Parameters
- **Personal Info**: Height, weight, age, sex
- **Goals**: Weight loss, muscle building, athletic performance, body recomposition, health improvement
- **Activity Levels**: Detailed exercise intensity and daily activity assessments
- **Dietary Preferences**: Vegetarian, vegan, keto, paleo, Mediterranean, etc.
- **Allergies & Intolerances**: Comprehensive allergy and intolerance tracking
- **Macro Preferences**: Balanced, high-protein, low-carb, high-carb ratios
- **Meal Frequency**: Customizable meals per day (1-8)

### Advanced Features
- **Water Intake Calculator**: Personalized hydration recommendations
- **Supplement Suggestions**: Evidence-based supplement recommendations
- **Shopping List Generation**: Organized grocery lists by category
- **Recipe Ideas**: Detailed recipes with macro breakdowns
- **Meal Prep Tips**: Practical advice for meal preparation
- **Responsive Design**: Works on desktop, tablet, and mobile devices

## Technology Stack

- **Frontend**: Next.js 14, React, TypeScript, Tailwind CSS
- **Backend**: Next.js API Routes, Prisma ORM
- **Database**: PostgreSQL (configured, ready for deployment)
- **Authentication**: NextAuth.js (schema ready)
- **PDF Generation**: jsPDF with custom templates
- **Form Handling**: React Hook Form with Zod validation
- **UI Components**: Custom components with Radix UI primitives

## Quick Start

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Environment Setup**
   ```bash
   cp .env .env.local
   ```
   
   Configure your `.env.local`:
   ```
   DATABASE_URL="postgresql://username:password@localhost:5432/diet_calculator"
   NEXTAUTH_SECRET="your-secret-key"
   NEXTAUTH_URL="http://localhost:3000"
   HUGGINGFACE_API_KEY="your-huggingface-key"  # Optional for AI features
   ```

3. **Database Setup** (Optional for full functionality)
   ```bash
   npx prisma migrate dev
   npx prisma generate
   ```

4. **Start Development Server**
   ```bash
   npm run dev
   ```

5. **Access Application**
   Open [http://localhost:3000](http://localhost:3000)

## Usage

1. **Complete the Form**: Fill out the 4-step calculator form with your personal information, goals, activity levels, and dietary preferences.

2. **Get Results**: View personalized nutrition targets including daily calories, macronutrient breakdown, and metabolic calculations.

3. **Review Meal Plan**: Examine AI-generated meal suggestions, recipes, and shopping lists tailored to your preferences.

4. **Download Report**: Generate and download a comprehensive PDF report with all recommendations and meal plans.

## Project Structure

```
src/
├── app/
│   ├── api/generate-meal-plan/    # AI meal generation endpoint
│   ├── globals.css                # Global styles with CSS variables
│   ├── layout.tsx                 # Root layout
│   └── page.tsx                   # Main page
├── components/
│   ├── ui/                        # Reusable UI components
│   └── DietCalculatorForm.tsx     # Main calculator form
├── lib/
│   ├── calculations.ts            # BMR/TDEE calculation logic
│   ├── pdf-generator.ts           # PDF report generation
│   ├── prisma.ts                  # Database client
│   └── utils.ts                   # Utility functions
└── prisma/
    └── schema.prisma              # Database schema
```

## Nutritional Calculations

### BMR (Basal Metabolic Rate)
Uses the Mifflin-St Jeor equation:
- **Men**: BMR = 10 × weight(kg) + 6.25 × height(cm) - 5 × age + 5
- **Women**: BMR = 10 × weight(kg) + 6.25 × height(cm) - 5 × age - 161

### TDEE (Total Daily Energy Expenditure)
BMR multiplied by activity factors:
- **Sedentary**: 1.2
- **Lightly Active**: 1.375
- **Moderately Active**: 1.55
- **Very Active**: 1.725
- **Extremely Active**: 1.9

Additional exercise intensity multipliers applied for comprehensive calculation.

## Customization

### Adding New Dietary Preferences
1. Update the form options in `DietCalculatorForm.tsx`
2. Modify the AI prompt in `api/generate-meal-plan/route.ts`
3. Update the database schema if needed

### Extending PDF Reports
Customize the PDF layout and content in `lib/pdf-generator.ts`:
- Add new sections
- Modify styling
- Include additional data points

### Database Integration
The app includes a complete Prisma schema for:
- User management
- Diet calculations storage
- Progress tracking
- Recipe database
- Food nutrition data

## AI Integration

The application integrates with **Hugging Face** for AI-powered meal planning:

### Setup Hugging Face Integration:
1. Get your free API key from [Hugging Face](https://huggingface.co/settings/tokens)
2. Add `HUGGINGFACE_API_KEY="your-key-here"` to your `.env.local` file
3. The system automatically falls back to intelligent mock data if no API key is provided

### Features:
- **Smart Meal Generation**: Uses Hugging Face's language models to create personalized meal plans
- **Dietary Adaptations**: Automatically adjusts recommendations based on preferences, allergies, and goals
- **Fallback System**: Works without API key using structured meal planning algorithms
- **Multiple Model Support**: Can be configured to use different Hugging Face models

### Supported Models:
- DialoGPT (default for conversational meal planning)
- Llama 2, Mistral, or other text generation models
- Easy to switch models by changing the endpoint in `huggingface-client.ts`

### Benefits over other AI services:
- **Free tier available** with generous limits
- **Open source models** ensure transparency
- **Privacy focused** with optional local deployment
- **Cost effective** for production use

## Deploy on Vercel

The easiest way to deploy your Next.js app is to use the [Vercel Platform](https://vercel.com/new?utm_medium=default-template&filter=next.js&utm_source=create-next-app&utm_campaign=create-next-app-readme) from the creators of Next.js.

Check out our [Next.js deployment documentation](https://nextjs.org/docs/app/building-your-application/deploying) for more details.

---

**Built with ❤️ using modern web technologies and AI-powered nutrition science.**
